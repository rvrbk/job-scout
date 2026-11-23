<?php

$landing = [
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

$landing['pages'] = [
    'how-it-works' => [
        'title' => 'How Job Scout works',
        'heading' => 'How Job Scout works',
        'description' => 'Build a ledger of confirmed facts, find or add a job, and draft a CV or cover letter where every sentence is checked against the fact behind it.',
        'intro' => 'Job Scout turns what you have really done into job applications you can stand behind. This is the whole flow, from a few sentences about your work to a checked PDF.',
        'numbered' => true,
        'sections' => array_map(fn (array $step): array => ['heading' => $step['title'], 'body' => $step['description']], $landing['steps']),
    ],
    'features' => [
        'title' => 'Features',
        'heading' => 'What Job Scout does',
        'description' => 'A fact ledger, job search scored against your experience, your own vacancies, and CV and cover letter drafts with every claim checked, in your tone, exported as PDF or Markdown.',
        'intro' => 'Everything Job Scout does is built around one rule: an application may only say what you can back up.',
        'numbered' => false,
        'sections' => [
            ['heading' => 'Fact ledger', 'body' => 'Write what you have done, or import your CV. It is split into roles, projects, skills and single facts, and nothing counts until you confirm it.'],
            ['heading' => 'Job search scored against you', 'body' => 'Postings are gathered from several sources and scored against your ledger, with the reasons for the score and the gaps.'],
            ['heading' => 'Your own vacancies', 'body' => 'Paste the text of a vacancy or link to it. The title, company and requirements are read out, and the vacancy is saved with your other jobs.'],
            ['heading' => 'Drafts with receipts', 'body' => 'Draft a CV or cover letter for a vacancy, or a general one from the ledger alone. Each sentence cites the facts that support it and is checked against them. A sentence no confirmed fact supports is flagged instead of exported.'],
            ['heading' => 'Your tone and look', 'body' => 'Choose a colour style, a font and a tone, or provide a sample of your own writing so the wording matches your voice. The sample only influences wording and is never a source of facts.'],
            ['heading' => 'Review, approve, download', 'body' => 'You review every draft and approve it before export as a PDF or Markdown file. Nothing is sent to anyone on your behalf.'],
        ],
    ],
    'faq' => [
        'title' => 'Frequently asked questions',
        'heading' => 'Frequently asked questions',
        'description' => 'Answers about how Job Scout stops made-up claims, adding your own vacancies, writing in your voice, drafting without a job, and why it never applies for you.',
        'intro' => 'The questions people ask most about Job Scout.',
        'numbered' => false,
        'sections' => array_map(fn (array $faq): array => ['heading' => $faq['question'], 'body' => $faq['answer']], $landing['faqs']),
    ],
];

return $landing;
