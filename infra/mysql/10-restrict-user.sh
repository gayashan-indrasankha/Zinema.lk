# Sourced by the pinned official entrypoint on a NEW isolated empty volume only.
# No application tables, legacy migration or production data.
case "${MYSQL_DATABASE:-}" in
    zinema_recovery_development|zinema_recovery_test) ;;
    *) mysql_error "Unexpected local database identity"; return 1 ;;
esac
case "${MYSQL_USER:-}" in
    zinema_recovery_development|zinema_recovery_staging|zinema_recovery_ci) ;;
    *) mysql_error "Unexpected local application identity"; return 1 ;;
esac
escaped_database="${MYSQL_DATABASE//_/\\_}"
docker_process_sql --database=mysql <<EOSQL
REVOKE ALL PRIVILEGES ON \`${escaped_database}\`.* FROM '${MYSQL_USER}'@'%';
GRANT SELECT, INSERT, UPDATE, DELETE ON \`${escaped_database}\`.* TO '${MYSQL_USER}'@'%';
EOSQL
