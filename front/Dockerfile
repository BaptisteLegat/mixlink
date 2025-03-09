FROM node:18-alpine

RUN apk add --no-cache libc6-compat

WORKDIR /app

COPY package*.json ./

RUN npm install --arch=x64

COPY . .

EXPOSE 3000

CMD ["npm", "run", "dev"]
