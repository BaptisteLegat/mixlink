# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/)
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]
<!-- write your changes here -->

## [1.1.0] - 2025-07-10

### Added
- Complete OpenAPI/Swagger documentation for all API endpoints
- Documentation for Authentication endpoints (OAuth, login, logout, user profile)
- Documentation for Subscription endpoints (subscribe, cancel, change plan)
- Documentation for Provider endpoints (disconnect OAuth providers)
- Documentation for Contact form endpoint
- Documentation for Webhook endpoints (Stripe integration)
- Documentation for Plan endpoints (list available plans)
- OpenAPI schema definitions for all data models (UserModel, SubscriptionModel, PlanModel, ProviderModel, ContactModel)
- Comprehensive API documentation accessible at `/api/doc`

### Fixed
- Fixed HTTP method constraints on OAuth authentication routes
- Resolved schema reference issues in OpenAPI documentation
- Improved API response consistency across all endpoints

### Changed
- Enhanced API documentation structure with proper tags and descriptions
- Improved error response formatting for better client integration

## [1.0.0] - 2025-07-08

### Added
- Deployment of the application to the production server.
- Implementation of the `CHANGELOG.md` file
- Initialization of the versioning system with a changelog.
- Adoption of Semantic Versioning (SemVer)
- Definition of the release process (branch `release/*`, PR to `master`, tag, etc.)
- Implementation of automated release CI/CD: creation of GitHub Releases from `CHANGELOG.md` when pushing a `v*` tag.
