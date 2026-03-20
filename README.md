Hard reset naar een specifieke commit: LET OP, alles in gitignore gaat verloren

git fetch --all
git switch -C restart-from-commit <commit-hash>
git reset --hard <commit-hash>
git clean -fdx 