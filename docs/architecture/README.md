# iTop cloud inventory architecture (arc42)

The [main AsciiDoc document](arc42-template.adoc) assembles the complete architecture. Open a chapter below to read it directly on GitHub.

The chapters use the [English arc42 source](https://github.com/arc42/arc42-template/tree/88cc1d6c867980a45facc959f428cdb2dfccb6d7/EN/adoc). Keep the upstream `arc42help` blocks when editing. Add system-specific text and table rows outside those blocks so that both editions contain the project documentation.

## HTML editions

When a push changes these sources, the renderer, its npm dependencies or the workflow, the [Architecture HTML workflow](../../.github/workflows/architecture-html.yml) builds both editions from that commit. In GitHub Actions, select the run and download **architecture-html** under **Artifacts**. Open `cloud-inventory.html` for the project or `cloud-inventory-with-help.html` for the edition with template guidance. Both include their styles and diagrams.

To preview local edits, run these commands from the root of this repository:

```sh
npm ci --cache .tmp/npm-cache
npm run docs:architecture
```

The files are written to `.tmp/architecture-html/`. To choose another directory, run `npm run docs:architecture -- <output-directory>`.

## Chapters

1. [Introduction and Goals](adoc/01_introduction_and_goals.adoc)
2. [Architecture Constraints](adoc/02_architecture_constraints.adoc)
3. [Context and Scope](adoc/03_context_and_scope.adoc)
4. [Solution Strategy](adoc/04_solution_strategy.adoc)
5. [Building Block View](adoc/05_building_block_view.adoc)
6. [Runtime View](adoc/06_runtime_view.adoc)
7. [Deployment View](adoc/07_deployment_view.adoc)
8. [Cross-cutting Concepts](adoc/08_concepts.adoc)
9. [Architecture Decisions](adoc/09_architecture_decisions.adoc)
10. [Quality Requirements](adoc/10_quality_requirements.adoc)
11. [Risks and Technical Debts](adoc/11_technical_risks.adoc)
12. [Glossary](adoc/12_glossary.adoc)
