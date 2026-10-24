FROM mysql:8.4.13@sha256:76f0358884cb92d9a086d24e5671c38048d3fef40f2588703d73eb55392d6b69
COPY --chmod=0644 infra/mysql/10-restrict-user.sh /docker-entrypoint-initdb.d/10-restrict-user.sh
USER mysql
