# --- Stage 1: Build the TypeScript application ---
FROM node:20-alpine AS builder

WORKDIR /usr/src/app

# Copy package management files
COPY package*.json ./

# Install ALL dependencies (including TypeScript devDependencies)
RUN npm install

# Copy all source files
COPY . .

# Compile TypeScript to JavaScript (Creates the 'dist/' folder)
RUN npm run build


# --- Stage 2: Run the production application ---
FROM node:20-alpine

WORKDIR /usr/src/app

# Copy package management files
COPY package*.json ./

# Install ONLY production dependencies to keep the image small
RUN npm ci --only=production

# Copy the compiled files from the builder stage
COPY --from=builder /usr/src/app/dist ./dist

# Expose port 8080 (Cloud Run's default expected port)
ENV PORT=8080
EXPOSE 8080

# Start the application using your production start script
CMD ["npm", "start"]
