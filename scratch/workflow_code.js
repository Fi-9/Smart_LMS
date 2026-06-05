import { workflow, node, trigger, ifElse, merge, expr } from '@n8n/workflow-sdk';

const webhook = trigger({
  type: 'n8n-nodes-base.webhook',
  version: 2,
  config: {
    name: 'Webhook',
    parameters: {
      httpMethod: 'POST',
      path: 'smartlms-vision-v3',
      responseMode: 'responseNode',
      options: {
        rawBody: true,
        binaryData: true,
        binaryPropertyName: 'data'
      }
    },
    position: [220, 320]
  },
  output: [{}]
});

const pickImageBinary = node({
  type: 'n8n-nodes-base.code',
  version: 2,
  config: {
    name: 'Pick Image Binary',
    parameters: {
      jsCode: "const item = $input.first();\nconst binary = item.binary || {};\nconst imageKey = Object.keys(binary).find((key) => binary[key]?.mimeType?.startsWith('image/'));\nif (!imageKey) {\n  throw new Error('No image binary found in webhook payload');\n}\nconst image = binary[imageKey];\nreturn [{\n  json: {\n    imageKey,\n    mimeType: image.mimeType || 'image/jpeg'\n  },\n  binary: item.binary\n}];"
    },
    position: [420, 320]
  },
  output: [{ imageKey: 'data', mimeType: 'image/jpeg' }]
});

const buildGeminiPayload = node({
  type: 'n8n-nodes-base.code',
  version: 2,
  config: {
    name: 'Build Gemini Payload',
    parameters: {
      jsCode: "const item = $input.first();\nconst imageKey = item.json.imageKey;\nconst binaryDataBuffer = await this.helpers.getBinaryDataBuffer(0, imageKey);\nif (!binaryDataBuffer) {\n  throw new Error('Image payload is empty');\n}\nconst base64 = binaryDataBuffer.toString('base64');\nconst prompt = `You are a librarian cataloguing system. Analyze the uploaded book cover image. Return ONLY valid JSON with this shape: {\\n  \"isbn\": null,\\n  \"title\": null,\\n  \"author\": null,\\n  \"publisher\": null,\\n  \"category\": null\\n}`;\nreturn [{\n  json: {\n    contents: [{\n      role: 'user',\n      parts: [\n        { text: prompt },\n        {\n          inline_data: {\n            mime_type: item.json.mimeType || 'image/jpeg',\n            data: base64\n          }\n        }\n      ]\n    }]\n  }\n}];"
    },
    position: [620, 320]
  },
  output: [{ contents: [{ role: 'user', parts: [{ text: '' }, { inline_data: { mime_type: '', data: '' } }] }] }]
});

const callGeminiVision = node({
  type: 'n8n-nodes-base.httpRequest',
  version: 4.2,
  config: {
    name: 'Call Gemini Vision',
    parameters: {
      method: 'POST',
      url: expr('https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash:generateContent?key={{$("Webhook").item.json.headers["x-gemini-api-key"]}}'),
      sendBody: true,
      specifyBody: 'json',
      jsonBody: expr('{{ JSON.stringify($json) }}'),
      options: {
        timeout: 30000
      }
    },
    position: [860, 320]
  },
  settings: {
    retryOnFail: true,
    maxTries: 3,
    waitBetweenTries: 2000
  },
  output: [{ candidates: [{ content: { parts: [{ text: '{"title":"Atomic Habits","author":"James Clear","isbn":"9786020626314"}' }] } }] }]
});

const prepareLookups = node({
  type: 'n8n-nodes-base.code',
  version: 2,
  config: {
    name: 'Prepare Lookups',
    parameters: {
      jsCode: "const body = $input.first().json;\nconst text = body?.candidates?.[0]?.content?.parts?.[0]?.text || '';\nlet parsed = {};\nconst clean = text.replace(/```json\\n?|```/g, '').trim();\ntry {\n  parsed = JSON.parse(clean);\n} catch (error) {\n  const match = clean.match(/\\{[\\s\\S]*\\}/);\n  if (match) {\n    parsed = JSON.parse(match[0]);\n  }\n}\nconst title = typeof parsed.title === 'string' ? parsed.title.trim() : null;\nconst author = typeof parsed.author === 'string' ? parsed.author.trim() : null;\nconst isbn = typeof parsed.isbn === 'string' ? parsed.isbn.replace(/[^0-9Xx]/g, '') : null;\nconst queryTitle = title ? `intitle:${title}` : null;\nconst googleUrl = isbn\n  ? `https://www.googleapis.com/books/v1/volumes?q=isbn:${isbn}`\n  : (queryTitle ? `https://www.googleapis.com/books/v1/volumes?q=${encodeURIComponent(queryTitle + (author ? ' inauthor:' + author : ''))}&maxResults=3` : null);\nconst openLibraryUrl = isbn\n  ? `https://openlibrary.org/isbn/${isbn}.json`\n  : (title ? `https://openlibrary.org/search.json?title=${encodeURIComponent(title)}${author ? `&author=${encodeURIComponent(author)}` : ''}&limit=1` : null);\nconst websearchQuery = title\n  ? `Buku ${title}${author ? ' ' + author : ''} site:gramedia.com OR site:gramedia.digital`\n  : null;\nreturn [{\n  json: {\n    gemini: parsed,\n    googleUrl,\n    openLibraryUrl,\n    websearchQuery\n  }\n}];"
    },
    position: [1080, 320]
  },
  output: [{ gemini: {}, googleUrl: '', openLibraryUrl: '', websearchQuery: '' }]
});

const hasGoogleUrl = ifElse({
  version: 2.3,
  config: {
    name: 'Has Google URL',
    parameters: {
      conditions: {
        conditions: [
          {
            leftValue: expr('{{$json.googleUrl}}'),
            operator: {
              type: 'string',
              operation: 'isNotEmpty'
            }
          }
        ]
      }
    },
    position: [1280, 220]
  }
});

const googleBooks = node({
  type: 'n8n-nodes-base.httpRequest',
  version: 4.2,
  config: {
    name: 'Google Books',
    parameters: {
      method: 'GET',
      url: expr('{{$json.googleUrl}}'),
      options: {
        timeout: 15000
      }
    },
    position: [1480, 180]
  },
  output: [{ items: [{ volumeInfo: {} }] }]
});

const hasOpenLibraryUrl = ifElse({
  version: 2.3,
  config: {
    name: 'Has Open Library URL',
    parameters: {
      conditions: {
        conditions: [
          {
            leftValue: expr('{{$json.openLibraryUrl}}'),
            operator: {
              type: 'string',
              operation: 'isNotEmpty'
            }
          }
        ]
      }
    },
    position: [1280, 360]
  }
});

const openLibrary = node({
  type: 'n8n-nodes-base.httpRequest',
  version: 4.2,
  config: {
    name: 'Open Library',
    parameters: {
      method: 'GET',
      url: expr('{{$json.openLibraryUrl}}'),
      options: {
        timeout: 15000
      }
    },
    position: [1480, 400]
  },
  output: [{ title: '', publishers: [], covers: [] }]
});

const hasWebsearchQuery = ifElse({
  version: 2.3,
  config: {
    name: 'Has Websearch Query',
    parameters: {
      conditions: {
        conditions: [
          {
            leftValue: expr('{{$json.websearchQuery}}'),
            operator: {
              type: 'string',
              operation: 'isNotEmpty'
            }
          }
        ]
      }
    },
    position: [1280, 520]
  }
});

const tavilyWebsearch = node({
  type: 'n8n-nodes-base.httpRequest',
  version: 4.2,
  config: {
    name: 'Tavily Websearch',
    parameters: {
      method: 'POST',
      url: 'https://api.tavily.com/search',
      sendHeaders: true,
      headerParameters: {
        parameters: [
          {
            name: 'Authorization',
            value: expr('Bearer {{$("Webhook").item.json.headers["x-tavily-api-key"]}}')
          }
        ]
      },
      sendBody: true,
      specifyBody: 'json',
      jsonBody: expr('{{ JSON.stringify({ query: $json.websearchQuery, max_results: 3, search_depth: "basic", include_answer: false, include_raw_content: false, include_images: false }) }}'),
      options: {
        timeout: 20000
      }
    },
    position: [1490, 600]
  },
  output: [{ results: [{ content: '', url: '' }] }]
});

const mergeInputs = merge({
  version: 3,
  config: {
    name: 'Merge Inputs',
    parameters: {
      mode: 'combine',
      combineBy: 'combineAll'
    },
    position: [1720, 360]
  }
});

const mergeToJson = node({
  type: 'n8n-nodes-base.code',
  version: 2,
  config: {
    name: 'Merge To JSON',
    parameters: {
      jsCode: "const entries = $input.all().map((item) => item.json || {});\nconst root = entries.find((entry) => entry.gemini) || {};\nconst gemini = root.gemini || {};\nconst googleRaw = entries.find((entry) => Array.isArray(entry.items));\nconst openLibraryRaw = entries.find((entry) => entry.title || entry.publishers || Array.isArray(entry.docs));\nconst tavilyRaw = entries.find((entry) => Array.isArray(entry.results));\n\nlet google = {};\nif (googleRaw?.items?.length) {\n  const info = googleRaw.items[0]?.volumeInfo || {};\n  let isbn = null;\n  for (const identifier of info.industryIdentifiers || []) {\n    if (identifier?.identifier) {\n      isbn = String(identifier.identifier).replace(/[^0-9Xx]/g, '');\n      if ((identifier.type || '').toUpperCase() === 'ISBN_13') break;\n    }\n  }\n  google = {\n    title: info.title || null,\n    author: Array.isArray(info.authors) ? info.authors.join(', ') : null,\n    isbn,\n    publisher: info.publisher || null,\n    published_year: info.publishedDate ? parseInt(String(info.publishedDate).substring(0, 4), 10) : null,\n    description: info.description || null,\n    category: Array.isArray(info.categories) ? info.categories[0] : null,\n    cover_url: info.imageLinks?.thumbnail ? String(info.imageLinks.thumbnail).replace('http://', 'https://') : null,\n    source: 'google'\n  };\n}\n\nlet openLibrary = {};\nif (Array.isArray(openLibraryRaw?.docs) && openLibraryRaw.docs.length) {\n  const doc = openLibraryRaw.docs[0];\n  openLibrary = {\n    title: doc.title || null,\n    author: Array.isArray(doc.author_name) ? doc.author_name.join(', ') : null,\n    isbn: Array.isArray(doc.isbn) ? String(doc.isbn[0]).replace(/[^0-9Xx]/g, '') : null,\n    publisher: Array.isArray(doc.publisher) ? doc.publisher[0] : null,\n    published_year: doc.first_publish_year || null,\n    description: null,\n    category: Array.isArray(doc.subject) ? doc.subject[0] : null,\n    cover_url: doc.cover_i ? `https://covers.openlibrary.org/b/id/${doc.cover_i}-M.jpg` : null,\n    source: 'openlibrary'\n  };\n} else if (openLibraryRaw?.title) {\n  openLibrary = {\n    title: openLibraryRaw.title || null,\n    author: Array.isArray(openLibraryRaw.authors) ? openLibraryRaw.authors.map((entry) => entry?.name || entry).join(', ') : null,\n    isbn: Array.isArray(openLibraryRaw.isbn_13) ? String(openLibraryRaw.isbn_13[0]).replace(/[^0-9Xx]/g, '') : (gemini.isbn || null),\n    publisher: Array.isArray(openLibraryRaw.publishers) ? openLibraryRaw.publishers[0] : null,\n    published_year: openLibraryRaw.published_date || null,\n    description: typeof openLibraryRaw.description === 'string' ? openLibraryRaw.description : openLibraryRaw.description?.value || null,\n    category: null,\n    cover_url: Array.isArray(openLibraryRaw.covers) && openLibraryRaw.covers[0] ? `https://covers.openlibrary.org/b/id/${openLibraryRaw.covers[0]}-M.jpg` : null,\n    source: 'openlibrary'\n  };\n}\n\nlet websearch = {};\nif (Array.isArray(tavilyRaw?.results) && tavilyRaw.results.length) {\n  const top = tavilyRaw.results[0];\n  websearch = {\n    description: top.content || null,\n    source_url: top.url || null,\n    source: 'websearch'\n  };\n}\n\nconst pick = (...values) => values.find((value) => value !== null && value !== undefined && value !== '') || null;\nconst book = {\n  title: pick(google.title, openLibrary.title, gemini.title),\n  author: pick(google.author, openLibrary.author, gemini.author),\n  isbn: pick(google.isbn, openLibrary.isbn, gemini.isbn),\n  publisher: pick(google.publisher, openLibrary.publisher, gemini.publisher),\n  published_year: pick(google.published_year, openLibrary.published_year, null),\n  description: pick(google.description, openLibrary.description, websearch.description, null),\n  category: pick(google.category, openLibrary.category, gemini.category),\n  cover_url: pick(google.cover_url, openLibrary.cover_url, null),\n  source_url: pick(websearch.source_url, null)\n};\nconst hasCoreData = Boolean(book.title || book.author || book.isbn);\nreturn [{ \n  json: {\n    found: hasCoreData,\n    source: book.description && !google.description && !openLibrary.description ? 'websearch' : (google.title || google.isbn ? 'google' : (openLibrary.title || openLibrary.isbn ? 'openlibrary' : 'gemini')),\n    book,\n    sources_used: {\n      gemini_vision: true,\n      google_books: Boolean(google.title || google.isbn),\n      openlibrary: Boolean(openLibrary.title || openLibrary.isbn),\n      websearch: Boolean(websearch.description)\n    }\n  }\n}];"
    },
    position: [1940, 360]
  },
  output: [{}]
});

const respond = node({
  type: 'n8n-nodes-base.respondToWebhook',
  version: 1,
  config: {
    name: 'Respond',
    parameters: {
      respondWith: 'json',
      responseBody: expr('{{$json}}')
    },
    position: [2140, 360]
  },
  output: [{}]
});

const myWorkflow = workflow('xRZhptq03pAwX3qR', 'SmartLMS Vision + Websearch Fallback')
  .add(webhook)
  .to(pickImageBinary)
  .to(buildGeminiPayload)
  .to(callGeminiVision)
  .to(prepareLookups);

myWorkflow.add(prepareLookups).to(hasGoogleUrl
  .onTrue(googleBooks.to(mergeInputs.input(1)))
  .onFalse(mergeInputs.input(0))
);

myWorkflow.add(prepareLookups).to(hasOpenLibraryUrl
  .onTrue(openLibrary.to(mergeInputs.input(3)))
  .onFalse(mergeInputs.input(2))
);

myWorkflow.add(prepareLookups).to(hasWebsearchQuery
  .onTrue(tavilyWebsearch.to(mergeInputs.input(5)))
  .onFalse(mergeInputs.input(4))
);

myWorkflow.add(mergeInputs).to(mergeToJson).to(respond);

export default myWorkflow;
