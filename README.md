# News Aggregator API

Welcome to the **News Aggregator API**! This is a RESTful API built with **Laravel** that aggregates news articles from multiple sources and provides endpoints for a frontend application to consume. It includes user authentication, article management, user preferences, and data aggregation features.

---

## Features

1. **User Authentication**:
   - User registration and login using Laravel Sanctum for API token authentication.
   - Logout and password reset functionality.

2. **Article Management**:
   - Fetch articles with pagination.
   - Search articles by keyword, date, category, and source.
   - Retrieve details of a single article.

3. **User Preferences**:
   - Set and retrieve preferred news sources, categories, and authors.
   - Fetch a personalized news feed based on user preferences.

4. **Data Aggregation**:
   - Regularly fetch and store articles from at least 3 different news APIs.
   - Efficient data storage and indexing for optimized search and retrieval.

5. **API Documentation**:
   - Comprehensive API documentation using **Swagger/OpenAPI**.

6. **Testing**:
   - Unit and feature tests using PHPUnit.
   - Ensure high test coverage for core functionalities.

---

## Data Sources

The API fetches data from the following sources:
1. [NewsAPI](https://newsapi.org/)
2. [The Guardian](https://open-platform.theguardian.com/)
3. [New York Times](https://developer.nytimes.com/)

---

## Technologies Used

- **Backend**: Laravel (PHP)
- **Database**: MySQL
- **Authentication**: Laravel Sanctum
- **API Documentation**: Swagger/OpenAPI
- **Containerization**: Docker
- **Testing**: PHPUnit

---

## Setup Instructions

### Prerequisites

- PHP 8.2
- Laravel 10
- MySQL Server
- Docker & Docker Compose
- Composer
- API keys for the chosen news sources (NewsAPI, The Guardian, New York Times).

---

### Step 1: Clone the Repository
1. Open your terminal or command prompt.
2. Clone the repository using the following command:
   ```bash
   git clone https://github.com/amalop/news-aggregator-api.git
   ```
3. Navigate to the project directory:
   ```bash
   cd news-aggregator-api
   ```

### Step 2: Install Dependencies & Configure Environment Variables
1. Copy the `.env.example` file to `.env`:
   ```bash
   cp .env.example .env
   ```
2. Install PHP dependencies using Composer:
   ```bash
   composer install
   ```
3. Generate the application key:
   ```bash
   php artisan key:generate
   ```
4. Open the `.env` file in a text editor and update the following configurations:

#### Database Configuration:
```
DB_CONNECTION=mysql
DB_HOST=db
DB_PORT=3306
DB_DATABASE=news_aggregator
DB_USERNAME=user
DB_PASSWORD=password
```

#### API Keys:
Replace the placeholders with your actual API keys for the news sources:
```
NEWSAPI_KEY=your_newsapi_key
GUARDIAN_KEY=your_guardian_key
NYTIMES_KEY=your_nytimes_key
```

### Step 3: Run the Application with Docker

1. Build and start the Docker containers:
    ```bash
    docker-compose up -d --build
    ```
2. Run database migrations and seeders to set up the database:
    ```bash
    docker-compose exec app php artisan migrate --seed
    ```
3. Install npm dependencies for Swagger API documentation generation:
    ```bash
    docker-compose exec app npm install
    ```
4. Generate Swagger API documentation:
    ```bash
    docker-compose exec app php artisan l5-swagger:generate
    ```
5. The `fetch:news-articles` command is scheduled to run hourly to fetch and store news articles. This is handled by Laravel's task scheduler and cron.

### Step 4: Access the Application
1. The API will be available at: [http://localhost:8000](http://localhost:8000)
2. Access the Swagger API documentation at: [http://localhost:8000/api/documentation](http://localhost:8000/api/documentation)

### Step 5: Data Aggregation Scheduling
1. The `fetch:news-articles` command is scheduled to run hourly using Laravel's task scheduler and cron. This ensures that the latest news articles are fetched and stored in the database regularly.
2. Run the command manually or add it to a cron job for fetching articles:
   ```bash
   php artisan fetch:news-articles
   ```

### Step 6: Running Tests
Run PHPUnit tests to ensure the functionality of the API:
```bash
php artisan test
```
To run tests inside the Docker container:
```bash
docker-compose exec app php artisan test
```

---

## Contributing

1. Fork the repository.
2. Create a new branch:
   ```bash
   git checkout -b feature/your-feature-name
   ```
3. Commit your changes:
   ```bash
   git commit -m "Add your feature"
   ```
4. Push to the branch:
   ```bash
   git push origin feature/your-feature-name
   ```
5. Submit a pull request.

---

## License

This project is licensed under the MIT License. See the LICENSE file for details.

---

## Contact

For any questions or feedback, please reach out to your-email@example.com.

---

### Author
**Amal OP** - Backend Laravel Developer

