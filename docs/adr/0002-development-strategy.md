# ADR 0002: Development Strategy

Date: 2026-06-25

Status: Accepted

## Context

Zinema.lk – Modern Full-Stack Movie Streaming Platform will be built across product, architecture, backend, frontend, media, DevOps, testing, and documentation work. The development process must keep changes understandable and reduce delivery risk.

## Decision

The project will be built branch by branch, starting with public planning documentation. Each branch should have a focused purpose and a clear review scope.

## Why the Project Is Built Branch by Branch

Branch-based development keeps each phase isolated and reviewable. It makes it easier to understand what changed, test the intended behavior, and keep the stable branch clean.

Each branch should focus on one product or technical outcome, such as planning docs, solution foundation, catalog API, frontend shell, media processing, admin workspace, or deployment pipeline.

## Why Commits Should Be Small

Small commits make progress easier to review, test, and recover. They also create a useful history of decisions and implementation steps.

Small commits should:

- Represent one logical change.
- Use clear commit messages.
- Avoid mixing unrelated concerns.
- Include tests or documentation when relevant.
- Keep review feedback focused.

## Why Public Documentation Comes First

Public documentation sets a shared direction before implementation begins. It clarifies the product goal, target users, architecture, roadmap, and development strategy.

This helps maintain:

- Clear product scope.
- Shared technical direction.
- Consistent public presentation.
- Better branch planning.
- Easier onboarding for future contributors.

## How Quality Will Be Maintained

Quality will be maintained through:

- Clean Architecture boundaries.
- Module ownership rules.
- REST API consistency.
- Role-based authorization checks.
- Automated tests for important behavior.
- GitHub Actions build and test checks.
- Code review for each meaningful branch.
- ADRs for significant decisions.
- Documentation updates when scope or design changes.

## How Features Will Be Delivered Safely

Features should move through a predictable path:

1. Define the scope and expected behavior.
2. Update or add documentation when the feature changes the product or architecture.
3. Implement the backend contract and tests.
4. Implement the frontend flow and tests.
5. Verify role-based access and error handling.
6. Run local checks and CI checks.
7. Review the branch before merging.

For larger features, delivery should be split into smaller branches such as API contract, data model, admin workflow, public UI, and operational reporting.

## Branch Naming Guidance

Recommended branch names:

- `feature/00-product-planning`
- `feature/01-solution-foundation`
- `feature/02-catalog-api`
- `feature/03-frontend-shell`
- `feature/04-identity-access`
- `feature/05-media-processing`
- `feature/06-admin-workspace`
- `feature/07-ci-devops`

## Commit Message Guidance

Commit messages should be professional, short, and action-oriented.

Examples:

- `docs: add product planning overview`
- `docs: define target architecture`
- `docs: record initial architecture decisions`
- `chore: add solution foundation`
- `feat: add catalog browse API`
