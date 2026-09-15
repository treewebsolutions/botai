# Chat AI — Knowledge Base, Assistant, Conversații

Aplicația de tenant (`workspace/`) răspunde în widget-ul embed folosind **OpenAI Responses
API** cu unealta `file_search` peste un *vector store* care conține paginile scrapuite ale
site-ului. Implementarea este portată din `masteranunturi` (chei INT în loc de UUID) și
înlocuiește complet vechiul cod bazat pe Assistants API (`vector_store`,
`vector_store_file`, `thread`, endpoint-urile `/threads`).

## Entități

| Tabelă / model | Rol |
|---|---|
| `knowledge_base` (`common\models\KnowledgeBase`) | găzduiește **un** vector store OpenAI (`vector_store_id`, `vs_…`), creat la prima salvare a unui KB cu provider OpenAI (`KnowledgeBaseForm::save()` → `OpenAIResponsesService::ensureVectorStore()`) sau la prima sincronizare a unei pagini |
| `assistant` (`common\models\Assistant`) | profilul de chat configurabil din backend: `provider`, `model`, `instructions`, `temperature`, `top_p`, `max_tokens`, `type` (azi doar *Chat*), `default`. Nu se creează nimic pe partea OpenAI pentru un asistent |
| `assistant_knowledge_base` | legătura many-to-many; asistentul caută în vector store-urile tuturor KB-urilor active legate (`Assistant::collectVectorStoreIds()`) |
| `knowledge_base_document` (`common\models\KnowledgeBaseDocument`) | un fișier încărcat din backend (PDF, DOC/DOCX, PPTX, TXT, MD, HTML, JSON, max 50 MB) care extinde un KB dincolo de paginile scanate; stă în `uploads/knowledge-base-document/<id>/`, iar `openai_file_id` / `vector_store_file_id` / `vector_store_id` / `index_status` (0 neindexat / 1 indexat / 2 eroare) reflectă starea din vector store |
| `record_vector_index` (`common\models\RecordVectorIndex`) | un rând per pagină indexată: `record_id` = `page.id`, `openai_file_id`, `vector_store_file_id`, `vector_store_id`, `status` (0 neindexat / 1 indexat / 2 eroare), `indexed_at`, `error_message` |
| `conversation` (`common\models\Conversation`, ex `thread`) | o sesiune a widget-ului; `openai_conversation_id` este obiectul *Conversations API* care ține starea între ture **și** token-ul public pe care widget-ul îl păstrează în `localStorage` |
| `message` | turele conversației (`conversation_id`, `role` user/assistant, `content`, `status`) |
| `participant` | păstrat pentru operatorul uman care va putea intra într-o conversație |

Backend: **Nomenclature → Knowledge Bases / Documents / Assistants** (`nomenclature-manager`) și
**Conversations → Conversations / Messages / Participants** (`conversation-manager`).
Permisiunile sunt `{view,create,update,delete,restore}KnowledgeBase`,
`{…}KnowledgeBaseDocument` și `{…}Conversation` (`install/db/<tip>/_02_permissions.sql`, `common\models\AuthItem`).

## Indexarea paginilor

`common\services\OpenAiRecordVectorStoreService` scrie un fișier `.txt` per pagină
(titlu, meta description, limbă, URL și textul curat din HTML, max 200 000 caractere),
îl urcă la OpenAI și îl atașează vector store-ului KB-ului rezolvat de
`resolveKnowledgeBase()`:

1. param `pageVectorKnowledgeBaseId` (override după id), altfel
2. primul KB OpenAI activ legat de asistentul de chat (`Assistant::findChatAssistant()`), altfel
3. cel mai vechi KB OpenAI activ.

Fără KB activ **nu se face nimic** (nici măcar rânduri în `record_vector_index`).

O pagină aparține indexului când este `ACTIVE`, nu este ștearsă și are conținut
(`isDisplayable()` / `buildDisplayableQuery()`). Hook-urile:

- `common\components\Scraper::savePage()` → `scheduleSync()` după fiecare pagină salvată
  (rulează după răspuns, ca *shutdown function*);
- `PageController::actionDelete()` → soft delete: `scheduleWithdraw()`; ștergere definitivă:
  `detachPageIndexRow()` înainte de `delete()` + `scheduleRemotePurge()` după commit;
- `PageController::actionRestore()` → `scheduleSync()`.

Comenzi console (`workspace/yii` sau `workspaces/<domeniu>/yii`):

```bash
php yii ai-index/setup sk-...    # salvează cheia OpenAI (Integration implicită) + creează KB-ul "Website"
php yii ai-index/status          # acoperirea indexului
php yii ai-index/reindex [id]    # (re)indexează toate paginile afișabile sau una singură
php yii ai-index/withdraw <id>   # scoate o pagină din vector store
php yii ai-index/cleanup         # retrage paginile care nu mai sunt afișabile
php yii ai-index/reconcile       # lipsă + actualizate + retrase, pentru cron (recomandat la 30 min)
```

## Documente încărcate

`common\services\OpenAiDocumentVectorStoreService` urcă fișierul **așa cum este** (OpenAI
extrage și fragmentează textul) în vector store-ul KB-ului documentului
(`ensureVectorStore()` dacă KB-ul nu îl are încă), cu atributele `document_id`,
`source=document`, `name`. Spre deosebire de pagini, un document nu depinde de
`resolveKnowledgeBase()`: fiecare document merge în KB-ul lui (care trebuie să fie OpenAI).

- `KnowledgeBaseDocumentForm::save()` salvează fișierul și programează `scheduleSync()`
  (după răspuns) la creare, la schimbarea fișierului, a statusului sau a KB-ului;
  `syncDocument()` retrage singur documentul când nu mai este indexabil (inactiv, șters,
  fișier lipsă) și scrie rezultatul pe rând (`index_status`, `error_message`), fără excepții.
- `KnowledgeBaseDocumentController`: `download`, `reindex` (re-upload), soft delete →
  `scheduleWithdraw()`, ștergere definitivă → `describeRemote()` + `scheduleRemotePurge()`
  după commit, restaurare → `scheduleSync()`.
- `isChatAvailable()` consideră chat-ul operațional și când există doar documente indexate.
- Console: `php yii ai-index/reindex-documents [id]`; `ai-index/status` afișează și
  documentele active / indexate / în eroare.
## Fluxul widget-ului embed

Endpoint-urile modulului `frontend/modules/embed` (`ChatController`):

| Rută | Metodă | Rol |
|---|---|---|
| `chat` | GET | randează widget-ul |
| `chat` | POST `prompt`, `conversation_id` | o tură: găsește conversația după token, verifică cheia OpenAI, apelează `OpenAIResponsesService::createConversationResponse()` și salvează cele două mesaje; primul prompt devine `summary` |
| `chat/conversation` | POST | creează obiectul OpenAI (`createConversation()`) + rândul local; întoarce `conversation_id` |
| `chat/validate-conversation?id=` | GET | `{valid: bool}` pentru token-ul din `localStorage` |
| `chat/send-conversation` | POST JSON `email`, `conversation_id` | trimite transcrierea pe email (Markdown → HTML pentru răspunsurile asistentului) |
| `chat/speak` | POST | TTS (neschimbat) |

Configurarea unei ture (`ChatController::resolveChatConfig()`): asistentul implicit de tip
Chat (model, instrucțiuni, KB-uri legate, `temperature`/`top_p` — omise pentru modelele
`gpt-5*`, care le resping); fără asistent se folosesc `chatModel` / `chatInstructions` din
`common/config/params.php` și KB-ul din `resolveKnowledgeBase()`.

Răspunsurile de eroare către widget sunt `{error: "..."}`: `No prompt provided`,
`Missing conversation ID`, `Conversation not found.`, `The chat is not configured yet.`
(nu există Integration OpenAI activă cu cheie), `AI service is temporarily unavailable.`.

`web/js/chat.js` păstrează token-ul în `localStorage['conversation_id']`, îl validează la
încărcare și creează altul dacă a expirat; istoricul afișat este ținut de pagina părinte
(`embed.js`, cheia `conversation_<token>`).

## Parametri (`workspace/common/config/params.php`)

`pageVectorKnowledgeBaseId`, `chatAssistantId`, `chatModel`, `chatInstructions`,
`chatFileSearchMaxResults`, `chatFileSearchScoreThreshold`,
`pageVectorSemanticSearch*` (căutarea semantică directă în pagini, dezactivată implicit).

## Teste

`workspace/tests/unit/KnowledgeBaseAssistantTest.php`, `KnowledgeBaseDocumentTest.php`,
`ConversationPersistenceTest.php`, `EmbedChatFlowTest.php` — rulează cu `workspace/run-tests.sh` după
`tools/load-test-schema.sh workspace` (schema din `install/db/_01_structure.sql`). Tura
propriu-zisă (apelul OpenAI) nu este acoperită automat.

## Modelul MySQL Workbench

Fișierul `.mwb` nu se editează programatic: după instalarea unui tenant cu noua schemă,
sincronizează modelul din baza de date.
