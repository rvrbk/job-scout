<?php

return [
    'steps' => [
        ['title' => 'Build your ledger',
            'description' => 'Write what you have done, or import your CV. It is split into roles, projects, skills and single facts. Nothing counts until you confirm it.'],
        ['title' => 'Find or add a job',
            'description' => 'Search several job sources, or paste or link any vacancy. Each posting is scored against your ledger, with the reasons and the gaps.'],
        ['title' => 'Draft with receipts',
            'description' => 'Generate a CV or cover letter, for a job or in general. Every sentence is checked against the fact it cites, and unsupported ones are flagged.'],
        ['title' => 'Approve and download',
            'description' => 'Choose a colour style, font and tone, or match your own writing. Approve the draft, then export a PDF or Markdown file. Nothing is sent for you.'],
    ],
    'faqs' => [
        ['question' => 'What is Job Scout?',
            'answer' => 'Job Scout is a web application that finds jobs matching your real experience and drafts CVs and cover letters in which every claim traces back to a fact you have confirmed.'],
        ['question' => 'How does Job Scout stop made-up claims?',
            'answer' => 'Every sentence in a draft cites the facts from your ledger that support it. Job Scout checks each sentence against those facts, and a sentence that no confirmed fact supports is flagged for you instead of being exported.'],
        ['question' => 'Can I use a vacancy I found somewhere else?',
            'answer' => 'Yes. Paste the text of the vacancy or link to it. Job Scout reads out the title, company and requirements, scores it against your ledger and saves it with your other jobs.'],
        ['question' => 'Can it write in my own voice?',
            'answer' => 'Yes. Pick a tone, or provide a sample of your own writing so the wording matches your voice. The sample only influences wording and is never used as a source of facts.'],
        ['question' => 'Can I draft a CV without a specific job?',
            'answer' => 'Yes. A general CV or cover letter can be drafted from your ledger alone, and then reused or adapted later.'],
        ['question' => 'Does Job Scout apply to jobs for me?',
            'answer' => 'No. You review and approve each draft and download it as a PDF or Markdown file. Nothing is sent anywhere on your behalf.'],
    ],
];
