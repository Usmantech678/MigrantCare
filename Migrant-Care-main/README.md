# Migrant Care

Migrant Care is a comprehensive web-based platform designed to provide healthcare access and support for migrant workers. The platform includes a dual-portal system for both medical professionals (Doctors) and patients (Migrant Workers), integrating identity verification, health record management, and AI-driven health tools.

## Features

*   **Role-Based Portals:** Dedicated dashboards and functionality for both Doctors and Workers.
*   **Identity & KYC Verification:** Built-in verification processes including Aadhaar integration and other KYC methods.
*   **Worker Health Tracking:** Tools for maintaining patient records, tracking health history, and providing consent forms.
*   **AI Symptom Checker & Mental Health Screening:** Interactive AI tools for initial health assessments.
*   **Public Health Monitoring:** A dashboard for tracking health trends and potential disease hotspots.
*   **Multi-Language Support:** Language selection features to ensure accessibility for diverse users.

## Tech Stack

*   **Frontend & Backend Logic:** PHP
*   **Database:** MySQL (schema provided in `database.sql`)
*   **Machine Learning / AI Component:** Python (`hotspot_trainer.py`)
*   **Data:** Includes geographic datasets (e.g., `pincodes.csv`) for mapping and analysis.

## Project Structure

*   `pages/`: Contains the main application pages, organized by feature (authentication, dashboards, health tools, verification).
*   `assets/`: UI assets, CSS, and images.
*   `functions.php`: Contains core application business logic.
*   `db.php` & `database.sql`: Database connection configuration and schema.
*   `hotspot_trainer.py`: Python script for hotspot training/prediction.
*   Detailed architectural documentation can be found in:
    *   `BUSINESS_LAYER.md`
    *   `DATA_LAYER.md`
    *   `PRESENTATION_LAYER.md`

## Installation

1.  Clone the repository.
2.  Set up a local web server (like XAMPP or MAMP) with PHP and MySQL support.
3.  Import the `database.sql` file into your MySQL database.
4.  Update `db.php` with your database credentials if necessary.
5.  Serve the project folder through your local web server.
