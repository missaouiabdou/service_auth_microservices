FROM nginx:1.25-alpine

# Copy custom nginx configuration
COPY nginx.conf /etc/nginx/nginx.conf
COPY default.conf /etc/nginx/conf.d/default.conf

# Create cache directory
RUN mkdir -p /var/cache/nginx/client_temp \
    && chown -R nginx:nginx /var/cache/nginx

EXPOSE 80

CMD ["nginx", "-g", "daemon off;"]