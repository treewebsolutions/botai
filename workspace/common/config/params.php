<?php
return [
	// This parameter is used to remove a specific URL segment in order to get the right uploads URL
	'upload.ignoredBasePaths' => ['admin'],

	// Allow-list for every image upload (avatars, carousels, company logos). Both keys are
	// read together by the `file` validators; `mimeTypes` makes the validator sniff the
	// real content instead of trusting the submitted file name.
	'image.extensions' => ['jpeg', 'jpg', 'png', 'gif', 'webp'],
	'image.mimeTypes' => ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'],

	// Allow-list for non-image uploads (support-ticket attachments, documents).
	// Deliberately excludes anything the web server could be talked into executing.
	'file.extensions' => ['jpeg', 'jpg', 'png', 'gif', 'webp', 'pdf', 'txt', 'csv', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'zip'],
	'file.mimeTypes' => [
		'image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp',
		'application/pdf', 'text/plain', 'text/csv',
		'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
		'application/vnd.ms-excel', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
		'application/vnd.ms-powerpoint', 'application/vnd.openxmlformats-officedocument.presentationml.presentation',
		'application/zip', 'application/x-zip-compressed',
	],
	'defaultLanguage' => 'en-US',
	'languages' => [
		'en' => 'en-US',
		'ro' => 'ro-RO',
		'de' => 'de-DE',
	],

	'schedule.enabled' => false,

	'user.loginDuration' => 60 * 60 * 3600,
	'user.loginTokenExpiration' => 60 * 60 * 3600,
	'user.passwordResetTokenExpiration' => 60 * 60 * 3600,
	'user.signupTokenExpiration' => 60 * 60 * 3600,
	'user.accountActivation' => \common\models\User::ACCOUNT_ACTIVATION_CONFIRMATION,

	// Google Translate API key — set the real value in params-local.php (kept out of VCS)
	'google.translateKey' => '',

	// -------------------------------------------------------------------------
	// AI: OpenAI Responses API + Knowledge Base (vector store of the scraped pages)
	// See common/services/OpenAiRecordVectorStoreService and console ai-index/*.
	// -------------------------------------------------------------------------
	/**
	 * Optional PK override: Knowledge Base (OpenAI) hosting the page text files for semantic search.
	 * 0 = resolve from DB: the KB linked to the default chat Assistant, else any active OpenAI KB.
	 */
	'pageVectorKnowledgeBaseId' => 0,
	/**
	 * Optional PK override: Assistant whose instructions/model/temperature/knowledge-bases drive the embed chat.
	 * 0 = use the default active chat Assistant from DB if one exists, else the params-based config below.
	 */
	'chatAssistantId' => 0,
	/** Model used by the embed chat when no Assistant row is configured. */
	'chatModel' => 'gpt-5.4-mini',
	/** Fallback system instructions when no Assistant row is configured. */
	'chatInstructions' => 'You are the virtual assistant of this website. Answer only from the knowledge base (the indexed pages of the site). If the answer is not in the knowledge base, say so briefly and suggest contacting the site. Reply in the language of the user.',
	/** `file_search.max_num_results` for the chat call (OpenAI accepts 1–50, default 20). */
	'chatFileSearchMaxResults' => 20,
	/** Score threshold (0–1) for the chunks the model may ground on in the chat call; 0 keeps everything. */
	'chatFileSearchScoreThreshold' => 0.1,
	/** Max distinct pages collected from vector search (cap 250). */
	'pageVectorSemanticSearchMaxResults' => 50,
	/** Hard ceiling on iterative rounds against the vector store (each round = up to 50 hits). */
	'pageVectorSemanticSearchMaxRounds' => 2,
	/** TTL (seconds) for the in-app cache of `query → page_ids`. 0 disables caching. */
	'pageVectorSemanticSearchCacheTtl' => 0,
	/** Min relevance score (0–1) for vector store chunks; higher = stricter. */
	'pageVectorSemanticSearchScoreThreshold' => 0.7,
	/** OpenAI vector ranker (retrieval only): auto | none | default-2024-11-15 */
	'pageVectorSemanticSearchRanker' => 'default-2024-11-15',
	/** OpenAI may rewrite the visitor query for retrieval; false = search with the exact phrase. */
	'pageVectorSemanticSearchRewriteQuery' => true,
	/** When true and a KB is set, callers may use OpenAI vector search over the indexed pages. */
	'pageVectorSemanticSearch' => false,
];
