# Portfolio

The existing portfolio site of Paul Versteeg, designer: CV, selected projects, publications, talks and stories seen through a designer's eye. Paul is leaving Fresk Digital, the company he co-founded, so the site is being checked on content and sharpened in form for the next step in his career.

## How this repo works

This repo holds the context of the project, so people and AI tools work from the same source of truth.
Figma, ClickUp and Storybook stay the place where the work happens. This repo explains it.

- `CLAUDE.md` and `AGENTS.md`: the front door for AI tools
- `context/project/`: why the project exists, who is involved, where things live
- `context/platform/`: everything that holds for the whole product
- `context/features/`: one folder per feature, all with the same structure
- `context/research/`: research that is broader than a single feature

## Starting a new feature

```bash
./scripts/new-feature.sh my-feature
```

This copies `context/features/_template/` into a new folder.
