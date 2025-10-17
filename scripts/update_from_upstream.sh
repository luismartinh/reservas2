#!/usr/bin/env bash
set -euo pipefail

# ==============================
# Configurables (con defaults)
# ==============================
UPSTREAM_REMOTE="${UPSTREAM_REMOTE:-upstream}"      # remoto del repo base
DEFAULT_BRANCH="${DEFAULT_BRANCH:-main}"            # rama principal
UPSTREAM_DIR="${UPSTREAM_DIR:-basicb5}"             # carpeta del base
LOCAL_DIR="${LOCAL_DIR:-reservas2}"                 # carpeta renombrada en tu repo
STRATEGY="${STRATEGY:-merge}"                       # merge | rebase
AUTO_COMMIT="${AUTO_COMMIT:-yes}"                   # yes | no (para el remapeo)
PUSH="${PUSH:-yes}"                                 # yes | no
DRY_RUN="${DRY_RUN:-no}"                            # yes | no (muestra pasos, no escribe)

# ==============================
# Helpers
# ==============================
log() { printf "\033[1;34m[info]\033[0m %s\n" "$*"; }
warn(){ printf "\033[1;33m[warn]\033[0m %s\n" "$*"; }
err() { printf "\033[1;31m[err ]\033[0m %s\n" "$*" >&2; }

die() { err "$*"; exit 1; }

require_clean_worktree() {
  if ! git diff --quiet || ! git diff --cached --quiet; then
    die "Hay cambios sin commitear. Commit o stashea antes de continuar."
  fi
}

try_enable_rename_detection() {
  git config merge.renames true || true
  git config diff.renames true || true
  git config merge.renamelimit 999999 || true
  git config rerere.enabled true || true
}

detect_main_branch() {
  # Si upstream/HEAD está configurado, respetalo
  local detected
  detected=$(git symbolic-ref --short "refs/remotes/${UPSTREAM_REMOTE}/HEAD" 2>/dev/null | sed "s#^${UPSTREAM_REMOTE}/##" || true)
  if [[ -z "${detected}" ]]; then
    detected=$(git branch -r | sed -n "s# *${UPSTREAM_REMOTE}/\\(main\\|master\\)\$#\\1#p" | head -n1)
  fi
  echo "${detected:-$DEFAULT_BRANCH}"
}

remap_upstream_dir_to_local_dir() {
  # Si tras integrar apareció ${UPSTREAM_DIR}/, lo movemos a ${LOCAL_DIR}/
  if [[ -d "${UPSTREAM_DIR}" ]]; then
    if [[ ! -d "${LOCAL_DIR}" ]]; then
      warn "No existe ${LOCAL_DIR}/; lo creo."
      [[ "${DRY_RUN}" == "yes" ]] || mkdir -p "${LOCAL_DIR}"
    fi

    log "Detectado directorio '${UPSTREAM_DIR}/' (probable rename de upstream). Remapeando a '${LOCAL_DIR}/'..."
    # Incluir archivos ocultos y evitar error si está vacío
    shopt -s dotglob nullglob

    # Listar entradas a mover (archivos y subcarpetas)
    mapfile -t entries < <(find "${UPSTREAM_DIR}" -mindepth 1 -maxdepth 1)
    if [[ "${#entries[@]}" -eq 0 ]]; then
      log "'${UPSTREAM_DIR}/' está vacío. Lo elimino si procede."
      [[ "${DRY_RUN}" == "yes" ]] || rmdir "${UPSTREAM_DIR}" || true
      return
    fi

    # Mover con git mv para preservar historia
    for p in "${entries[@]}"; do
      # Si existe destino con el mismo nombre, dejamos que git reporte conflicto
      dest="${LOCAL_DIR}/$(basename "$p")"
      if [[ -e "${dest}" ]]; then
        warn "Conflicto: ya existe '${dest}'. Intento 'git mv' igualmente (puede requerir resolución manual)..."
      fi
      if [[ "${DRY_RUN}" == "yes" ]]; then
        echo "DRY-RUN: git mv \"$p\" \"${dest}\""
      else
        git mv "$p" "${dest}" || warn "git mv falló para '$p' → '${dest}'. Resolvés manualmente luego."
      fi
    done

    # Intentar eliminar la carpeta si quedó vacía
    if [[ "${DRY_RUN}" != "yes" ]]; then
      rmdir "${UPSTREAM_DIR}" 2>/dev/null || true
    fi

    if [[ "${AUTO_COMMIT}" == "yes" && "${DRY_RUN}" != "yes" ]]; then
      if ! git diff --cached --quiet; then
        git commit -m "chore: remapear ${UPSTREAM_DIR}/ -> ${LOCAL_DIR}/ tras merge/rebase desde ${UPSTREAM_REMOTE}"
        log "Commit de remapeo creado."
      else
        log "No hay cambios indexados para commitear en remapeo."
      fi
    else
      warn "AUTO_COMMIT=${AUTO_COMMIT} o DRY_RUN=${DRY_RUN}; no se creará commit automático."
    fi
  else
    log "No se detectó '${UPSTREAM_DIR}/' tras la integración. Nada que remapear."
  fi
}

# ==============================
# Main
# ==============================
log "Verificando estado del repo…"
require_clean_worktree
try_enable_rename_detection

log "Buscando rama principal de ${UPSTREAM_REMOTE}…"
MAIN_BRANCH=$(detect_main_branch)
log "Rama principal detectada: ${MAIN_BRANCH}"

# Asegurarnos de tener el remoto upstream configurado
if ! git remote get-url "${UPSTREAM_REMOTE}" >/dev/null 2>&1; then
  die "El remoto '${UPSTREAM_REMOTE}' no está configurado. Configuralo con:
  git remote add ${UPSTREAM_REMOTE} https://github.com/luismartinh/yii2-esqueleto-2025.git"
fi

log "git fetch ${UPSTREAM_REMOTE}"
[[ "${DRY_RUN}" == "yes" ]] || git fetch "${UPSTREAM_REMOTE}"

log "Checkout a rama local ${MAIN_BRANCH}"
[[ "${DRY_RUN}" == "yes" ]] || git checkout "${MAIN_BRANCH}"

if [[ "${STRATEGY}" == "rebase" ]]; then
  log "Rebase sobre ${UPSTREAM_REMOTE}/${MAIN_BRANCH}"
  if [[ "${DRY_RUN}" == "yes" ]]; then
    echo "DRY-RUN: git rebase ${UPSTREAM_REMOTE}/${MAIN_BRANCH}"
  else
    set +e
    git rebase "${UPSTREAM_REMOTE}/${MAIN_BRANCH}"
    rc=$?
    set -e
    if [[ $rc -ne 0 ]]; then
      warn "Rebase con conflictos. Resolvélos (git status), luego: git add … && git rebase --continue (o --abort)."
      exit $rc
    fi
  fi
else
  log "Merge de ${UPSTREAM_REMOTE}/${MAIN_BRANCH}"
  if [[ "${DRY_RUN}" == "yes" ]]; then
    echo "DRY-RUN: git merge --no-ff --no-edit ${UPSTREAM_REMOTE}/${MAIN_BRANCH}"
  else
    set +e
    git merge --no-ff --no-edit "${UPSTREAM_REMOTE}/${MAIN_BRANCH}"
    rc=$?
    set -e
    if [[ $rc -ne 0 ]]; then
      warn "Merge con conflictos. Resolvélos (git status), luego: git add … && git merge --continue (o --abort)."
      exit $rc
    fi
  fi
fi

# Remapeo por si el merge/rebase reintrodujo basicb5/
remap_upstream_dir_to_local_dir

if [[ "${PUSH}" == "yes" ]]; then
  log "Pusheando cambios a origin (${MAIN_BRANCH})…"
  if [[ "${DRY_RUN}" == "yes" ]]; then
    echo "DRY-RUN: git push origin ${MAIN_BRANCH}"
  else
    git push origin "${MAIN_BRANCH}"
  fi
else
  warn "PUSH=${PUSH}; no se enviará a origin automáticamente."
fi

log "Listo ✅"
