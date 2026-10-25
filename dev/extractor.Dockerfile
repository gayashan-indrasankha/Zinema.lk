FROM node@sha256:55fb5f5a527ea2c5c4a95f963e48ef90aa38c7b346054fdc8ee9957a2b55d205
WORKDIR /opt/zinema
COPY fb-video-api/package.json fb-video-api/package-lock.json ./
RUN npm ci --ignore-scripts --no-audit --no-fund
COPY fb-video-api/environment.js fb-video-api/extraction.js fb-video-api/app.js fb-video-api/index.js ./
COPY dev/extractor-entrypoint.sh ./
USER node
ENTRYPOINT ["sh","/opt/zinema/extractor-entrypoint.sh"]
