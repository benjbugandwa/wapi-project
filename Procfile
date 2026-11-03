web: bash scripts/railway-start.sh
worker: php artisan queue:work ${QUEUE_CONNECTION:-redis} --queue=${REDIS_QUEUE:-default} --sleep=3 --tries=${QUEUE_TRIES:-3} --timeout=${QUEUE_TIMEOUT:-120} --max-time=${QUEUE_MAX_TIME:-3600}
scheduler: php artisan schedule:work
