# {{ $name }}

> {{ $name }} helps job seekers find jobs that match their real experience, or takes a vacancy the user pastes or links to, then drafts a CV and cover letter in which every claim traces back to a fact the user has confirmed.

{{ $name }} is a web application by verbeek.ug. It is a private, login-only tool: the only public page is the landing page, and signed-in areas (ledger, jobs, documents, settings) are not intended for crawling or indexing.

## How it works

- **Ledger**: users build a ledger of what they have actually done (roles, projects, education), broken down into facts. Only facts the user confirms can be used in an application. An existing CV can be imported as unconfirmed entries.
- **Job search**: postings are gathered from several sources and scored against the ledger, with the reason for the score and the gaps.
- **Your own vacancies**: a user can paste the text of a vacancy or link to it. The title, company and description are read from the text or page, and the vacancy can be saved, searched and sorted by date.
- **Drafting**: CVs and cover letters can be drafted for a specific vacancy, or as a general document from the ledger alone. The user picks one of four PDF styles and a tone, and can supply a sample of their own writing so the wording matches their voice. Each sentence is checked against the facts it cites before the document can be approved and downloaded as PDF or Markdown.
- **Truthfulness**: the writing sample only influences wording. It is never a source of facts, and a sentence no confirmed fact supports is flagged for the user instead of being exported.

## Pages

- [Home]({{ $url }}): product overview and login.

## Contact

- Operator: [verbeek.ug](https://verbeek.ug/)
