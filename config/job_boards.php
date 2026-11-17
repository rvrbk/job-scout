<?php

/**
 * Company job boards Job Scout can suggest to a user. Every entry was checked against the provider's public board
 * and returned open jobs. The description is what the model sees when it picks boards for a profile, so say what the
 * company does and what its engineers work with.
 */
return [
    'catalog' => [
        'greenhouse:gitlab' => ['name' => 'GitLab', 'about' => 'DevOps platform, fully remote. Ruby on Rails, Go, Vue, Python.'],
        'greenhouse:wikimedia' => ['name' => 'Wikimedia Foundation', 'about' => 'Wikipedia and MediaWiki, remote. PHP, JavaScript, Python.'],
        'greenhouse:grafanalabs' => ['name' => 'Grafana Labs', 'about' => 'Observability tooling, remote. Go, TypeScript, some Python.'],
        'greenhouse:canonical' => ['name' => 'Canonical', 'about' => 'Ubuntu publisher, remote. Python, Go, C, Kubernetes.'],
        'greenhouse:catawiki' => ['name' => 'Catawiki', 'about' => 'Online auction marketplace in Amsterdam. Product engineering and data.'],
        'greenhouse:algolia' => ['name' => 'Algolia', 'about' => 'Search API company with remote and European roles. Go, Ruby, JavaScript, PHP client libraries.'],
        'greenhouse:stripe' => ['name' => 'Stripe', 'about' => 'Payments infrastructure. Ruby, Java, TypeScript, some PHP.'],
        'greenhouse:datadog' => ['name' => 'Datadog', 'about' => 'Monitoring platform. Go, Python, TypeScript.'],
        'greenhouse:elastic' => ['name' => 'Elastic', 'about' => 'Search and observability, distributed company. Java, Go, TypeScript.'],
        'greenhouse:mongodb' => ['name' => 'MongoDB', 'about' => 'Document database. C++, Go, Java, Python.'],
        'greenhouse:cloudflare' => ['name' => 'Cloudflare', 'about' => 'Network and edge platform. Go, Rust, TypeScript, Python.'],
        'greenhouse:vercel' => ['name' => 'Vercel', 'about' => 'Frontend cloud, remote. TypeScript, Next.js, Rust, Go.'],
        'greenhouse:pagerduty' => ['name' => 'PagerDuty', 'about' => 'Incident response platform. Scala, Go, TypeScript, Python.'],
        'greenhouse:airbnb' => ['name' => 'Airbnb', 'about' => 'Travel marketplace. Java, Ruby, TypeScript, React.'],
        'greenhouse:trivago' => ['name' => 'trivago', 'about' => 'Hotel search, Düsseldorf. Java, Python, TypeScript, data.'],
        'greenhouse:contentful' => ['name' => 'Contentful', 'about' => 'Headless CMS, Berlin. TypeScript, Node, React.'],
        'greenhouse:n26' => ['name' => 'N26', 'about' => 'Digital bank, Berlin. Kotlin, Java, Python, data.'],
        'greenhouse:bitpanda' => ['name' => 'Bitpanda', 'about' => 'Crypto and investing app, Vienna. Java, Python, TypeScript.'],
        'greenhouse:typeform' => ['name' => 'Typeform', 'about' => 'Online forms, Barcelona and remote. TypeScript, Node, React.'],
        'greenhouse:netlify' => ['name' => 'Netlify', 'about' => 'Web hosting and Jamstack platform, remote. Go, TypeScript.'],
        'greenhouse:mattermost' => ['name' => 'Mattermost', 'about' => 'Open source team chat. Go, React, TypeScript.'],
        'ashby:supabase' => ['name' => 'Supabase', 'about' => 'Open source Postgres backend, remote with EMEA roles. TypeScript, Elixir, Postgres.'],
        'ashby:temporal' => ['name' => 'Temporal', 'about' => 'Durable execution platform, remote. Go, TypeScript, Java, Python.'],
        'ashby:render' => ['name' => 'Render', 'about' => 'Cloud hosting, remote. Go, TypeScript, Python.'],
        'ashby:sanity' => ['name' => 'Sanity', 'about' => 'Headless CMS, Oslo and remote. TypeScript, React, Node.'],
        'ashby:livekit' => ['name' => 'LiveKit', 'about' => 'Realtime audio and video infrastructure, remote. Go, Rust, TypeScript, Python.'],
        'ashby:linear' => ['name' => 'Linear', 'about' => 'Issue tracking tool, remote. TypeScript, React, Node.'],
        'ashby:posthog' => ['name' => 'PostHog', 'about' => 'Product analytics, fully remote. Python, Django, TypeScript, React.'],
        'ashby:resend' => ['name' => 'Resend', 'about' => 'Email API for developers, remote. TypeScript, React, Node.'],
        'ashby:clerk' => ['name' => 'Clerk', 'about' => 'Authentication and user management, remote. TypeScript, React, Go.'],
        'ashby:vanta' => ['name' => 'Vanta', 'about' => 'Compliance automation. TypeScript, Node, Python, with EMEA roles.'],
        'ashby:airbyte' => ['name' => 'Airbyte', 'about' => 'Open source data integration, remote. Python, Java, TypeScript.'],
        'ashby:docker' => ['name' => 'Docker', 'about' => 'Container tooling. Go, TypeScript, Rust.'],
        'ashby:replit' => ['name' => 'Replit', 'about' => 'Browser based coding platform. TypeScript, Python, Go.'],
        'ashby:ramp' => ['name' => 'Ramp', 'about' => 'Finance automation. Python, TypeScript, Kotlin.'],
        'ashby:notion' => ['name' => 'Notion', 'about' => 'Workspace and notes app. TypeScript, React, Kotlin.'],
        'ashby:openai' => ['name' => 'OpenAI', 'about' => 'AI research and products. Python, TypeScript, Rust.'],
        'lever:neon' => ['name' => 'Neon', 'about' => 'Serverless Postgres, remote. Rust, TypeScript, Postgres.'],
    ],
];
