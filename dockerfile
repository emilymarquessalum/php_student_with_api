# Use the official PHP 8.2 command-line interface (CLI) image as the base.
# This is a lightweight image suitable for a simple server.
FROM php:8.2-cli

# Install the necessary system dependencies for the PostgreSQL extension.
RUN apt-get update && apt-get install -y \
    libpq-dev \
    && rm -rf /var/lib/apt/lists/*

# Install the PostgreSQL PDO driver for PHP.
RUN docker-php-ext-install pdo pdo_pgsql

# Set the working directory inside the container.
# This is where your repository files will be copied.
WORKDIR /usr/src/app

# Copy all files from the root of your published repository
# into the container's working directory.
COPY . .

# Expose port 8000.
# This informs Docker that the container will listen on this port,
# which is necessary for Render to route traffic to your application.
EXPOSE 8000

# Define the command to run when the container starts.
# We start the PHP built-in server.
# Using '0.0.0.0' tells the server to listen on all network interfaces,
# making it accessible from outside the container, which is required by Render.
# 'localhost' would only be accessible from within the container.
CMD ["php", "-S", "0.0.0.0:8000"]
