Hard reset naar een specifieke commit: LET OP, alles in gitignore gaat verloren

git fetch --all
git switch -C restart-from-commit <commit-hash>
git reset --hard <commit-hash>
git clean -fdx 


# Blog AI

A simple PHP multi-agent blog generator with:
- Research agent
- SEO agent
- Writer agent
- Reviewer agent
- Human approval UI
- JSON file storage for local development

## Requirements

- PHP 8.1+
- cURL enabled
- OpenAI API key

## Setup

```bash
cp .env.example .env