# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/)
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]
<!-- write your changes here -->

## [1.3.3] - 2025-08-28

### Added
- Add dependabot configuration for automated dependency updates by creating a `.github/dependabot.yml` file.

### Changed
- Updated Node.js version to 24 in Dockerfile and GitHub Actions workflows.
- Updated npm packages to their latest versions.

## [1.3.2] - 2025-08-15

### Fixed
- Fixed the build of front conflit with element-plus

## [1.3.1] - 2025-08-15

### Added

- Add redis image into Docker Compose for improved caching and performance.
- Add cache for search results in the session page.
- Improve the accessibility of the website.

### Fixed
- Fixed an issue where the log was writing errors for nothing when a non-logged in user is anonymous

## [1.3.0] - 2025-08-03

### Added
-  Search functionality for playlists in the session page, allowing users to quickly find specific song by title or artist (for host or participants).
- Add song into the playlist from the search results, enabling users to easily add songs to the session playlist. Everyone can see the added song.
- Remove songs from the playlist for the host, allowing them to manage the session playlist effectively. Everyone can see the removed song.
- Export functionality for playlists in an active session. The host can export the current session's playlist to Spotify, YouTube, or SoundCloud.
- History of playlists in the user profile, allowing users to view their previously exported playlists.

### Changed
- Updated the README.md file to include new features.

### Fixed
- PKCE (Proof Key for Code Exchange) for SoundCloud by removing a package who does not support it.

## [1.2.2] - 2025-07-27

### Added
- Added a custom authenticator (AuthTokenAuthenticator) for API routes in `security.yaml` to handle stateless authentication with access tokens.

### Fixed
- Fixed a bug where adding an email already used by another account to a SoundCloud user could cause a duplicate email constraint violation. Now, the SoundCloud user is merged with the existing user, and all providers are unified under a single account.
- Fixed an issue where connecting multiple times with the same SoundCloud account (without email) would create duplicate users without email. Now, the same SoundCloud account always maps to a single user.
- Fixed responsive layout and SoundCloud icon display on the profile page (frontend).

## [1.2.1] - 2025-07-26

### Added
- Add SoundCloud provider to the OAuth providers list

### Fixed
- Fixed the session cookie domain to be `.mix-link.fr`

## [1.2.0] - 2025-07-25

### Added
- Add session logic system
- Add session code generation
- Add session participant remove
- Add session participant kick
- Add session participant list
- Add session participant get by code
- Add session participant create
- Add Mercure integration

### Changed
- Updated the README.md file to include how to run the coverage tests and the configuration for use Stripe webhooks in local environment

## [1.1.2] - 2025-07-14

### Fixed
- Fixed an OAuth callback error causing a 500 Internal Server Error due to an invalid state or malformed Spotify response

## [1.1.1] - 2025-07-12

### Added
- Dockerfile (front and back) for production deployment

### Changed
- Updated the workflow of cicd.yml to include the Dockerfile.prod instead of Dockerfile

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
