#!/usr/bin/env sh
# Erstellt und pusht einen Release-Tag (löst assurewallos-docker.yaml auf GitHub aus).
# Nutzung: ./scripts/docker-release-tag.sh 0.1.2
set -eu

VERSION="${1:-}"
if [ -z "$VERSION" ]; then
  echo "Usage: $0 <version ohne v, z. B. 0.1.2>" >&2
  exit 1
fi

TAG="v${VERSION#v}"

if ! grep -q "^\$assure_version = '${VERSION#v}';" includes/insurance/assure_version.php 2>/dev/null; then
  echo "Warnung: includes/insurance/assure_version.php enthält nicht \$assure_version = '${VERSION#v}';" >&2
  echo "Bitte Version vor dem Tag anpassen." >&2
  read -r -p "Trotzdem fortfahren? [y/N] " ok
  case "$ok" in
    y|Y) ;;
    *) exit 1 ;;
  esac
fi

git tag -a "$TAG" -m "AssureWallos ${VERSION#v}"
echo "Tag $TAG erstellt. Push mit:"
echo "  GIT_ASKPASS= git push origin $TAG"
