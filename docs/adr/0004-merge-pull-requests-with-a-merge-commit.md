# 0004. Merge pull requests with a merge commit

- Status: Accepted
- Date: 2026-11-10

## Context

Each change is made on its own branch and checked by the CI in a pull request before it reaches `main`. Pull requests #9 to #18 were squash-merged: each one became a single commit on `main`.

Squashing gives a short history, but it erases the steps inside a pull request. In this project those steps carry information: a failing test committed first, then the code that makes it pass, then a refactoring. That order shows how the code was built, and it is part of what the repository is meant to show.

Options considered:

1. **Squash merge.** One commit per pull request; the intermediate steps are lost.
2. **Rebase merge.** Keeps the steps and gives a linear history, but nothing on `main` shows where a pull request starts and ends.
3. **Merge commit.** Keeps the steps, and the merge commit marks the pull request as a unit, with its number.

## Decision

From pull request #19 on, pull requests are merged with a merge commit. Commit messages follow [Conventional Commits](https://www.conventionalcommits.org/) (`feat:`, `fix:`, `test:`, `refactor:`, `docs:`, `chore:`, `ci:`, `style:`).

## Consequences

- The history of `main` shows each step of the work, and `git log --first-parent main` still gives one line per pull request.
- Every commit of a branch ends up on `main`, so commits must be clean before merging: a clear message, and no "fix typo" or "wip" commits left behind.
- The history keeps both styles: squashed commits up to #18, merge commits from #19.
