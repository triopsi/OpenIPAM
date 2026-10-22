#!/bin/sh
set -eu

# The Devcontainer mounts the host directory read-only. Copy it once so SSH
# can use files owned by the container user without changing the Mac's keys.
if [ ! -d /ssh-host ] || [ -e "$HOME/.ssh" ]; then
    exit 0
fi

umask 077
mkdir -p "$HOME/.ssh"
cp -R /ssh-host/. "$HOME/.ssh/"
find "$HOME/.ssh" -type d -exec chmod 700 {} +
find "$HOME/.ssh" -type f -exec chmod 600 {} +

# macOS SSH configurations may contain the Apple-only UseKeychain option.
# Ignore it in Linux while preserving identities, aliases and known_hosts.
if [ -f "$HOME/.ssh/config" ]; then
    { printf 'IgnoreUnknown UseKeychain\n'; cat "$HOME/.ssh/config"; } > "$HOME/.ssh/config.container"
    mv "$HOME/.ssh/config.container" "$HOME/.ssh/config"
fi
