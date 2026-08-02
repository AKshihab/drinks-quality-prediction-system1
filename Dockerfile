# Use Python 3.10, matching the project environment
FROM python:3.10-slim

# Prevent Python from creating cache files
ENV PYTHONDONTWRITEBYTECODE=1

# Show Python output immediately in Docker logs
ENV PYTHONUNBUFFERED=1

# Set the working directory inside the container
WORKDIR /app

# Copy the entire project into the container
COPY . .

# Upgrade pip and install project dependencies
RUN pip install --upgrade pip && \
    pip install --no-cache-dir -r requirements.txt

# Flask application port
EXPOSE 5000

# Start the Flask application
CMD ["python", "app.py"]