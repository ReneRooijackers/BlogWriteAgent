Hard reset naar een specifieke commit:

git fetch --all
git switch -C restart-from-commit <commit-hash>
git reset --hard <commit-hash>
git clean -fdx 