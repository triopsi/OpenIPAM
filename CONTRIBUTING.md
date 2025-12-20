# Contributing to OpenIPAM

- [Contributing to OpenIPAM](#contributing-to-openipam)
  - [🌍 Adding New Languages](#-adding-new-languages)
    - [Quick Start Guide](#quick-start-guide)
      - [1. Add Language Configuration](#1-add-language-configuration)
      - [2. Copy Translation Files](#2-copy-translation-files)
      - [3. Translate Content](#3-translate-content)
      - [4. Test Your Translation](#4-test-your-translation)
    - [Translation Guidelines](#translation-guidelines)
      - [Keys vs Values](#keys-vs-values)
      - [Placeholder Variables](#placeholder-variables)
      - [Context and Consistency](#context-and-consistency)
    - [Language-Specific Considerations](#language-specific-considerations)
      - [Right-to-Left Languages](#right-to-left-languages)
      - [Pluralization](#pluralization)
    - [Testing Your Translation](#testing-your-translation)
      - [Manual Testing](#manual-testing)
      - [Automated Testing](#automated-testing)
    - [Submitting Your Translation](#submitting-your-translation)
      - [1. Create a Pull Request](#1-create-a-pull-request)
      - [2. Include in Your PR](#2-include-in-your-pr)
      - [3. PR Description Template](#3-pr-description-template)
  - [🐛 Bug Reports](#-bug-reports)
    - [Before Reporting](#before-reporting)
    - [Bug Report Template](#bug-report-template)
  - [🚀 Feature Requests](#-feature-requests)
    - [Feature Request Template](#feature-request-template)
  - [💻 Development Setup](#-development-setup)
    - [With DevContainer (Development)](#with-devcontainer-development)
    - [Local Development](#local-development)
    - [DevContainer Setup](#devcontainer-setup)
    - [Running Tests](#running-tests)
  - [📝 Code Style](#-code-style)
    - [PHP Code Style](#php-code-style)
    - [Commit Messages](#commit-messages)
    - [Pull Request Process](#pull-request-process)
      - [1. Before Submitting](#1-before-submitting)
      - [2. PR Description](#2-pr-description)
      - [3. Review Process](#3-review-process)
  - [🏷️ Versioning](#️-versioning)
  - [📜 License](#-license)
  - [🤝 Community Guidelines](#-community-guidelines)
    - [Be Respectful](#be-respectful)
    - [Be Helpful](#be-helpful)
    - [Quality Standards](#quality-standards)
  - [📞 Getting Help](#-getting-help)
  - [🎉 Recognition](#-recognition)


Thank you for your interest in contributing to OpenIPAM! This document provides guidelines and instructions for contributing to the project.

## 🌍 Adding New Languages

OpenIPAM supports internationalization and we welcome translations in new languages! Adding a new language is straightforward thanks to our centralized configuration system.

### Quick Start Guide

#### 1. Add Language Configuration
Add your language to `config/languages.php`:

```php
'supported' => [
    // ... existing languages ...
    'your_code' => [
        'code' => 'your_code',           // ISO 639-1 language code (e.g., 'fr', 'es', 'it')
        'name' => 'Language Name',       // English name of the language
        'native_name' => 'Native Name',  // Native name (e.g., 'Français', 'Español')
        'flag_emoji' => '🇫🇷',          // Flag emoji for the country
        'flag_icon' => 'flag-icon-fr',   // CSS flag icon class (optional)
        'rtl' => false,                  // Set to true for right-to-left languages
    ],
],
```

#### 2. Copy Translation Files
Copy the English translation files as your starting point:

```bash
cp -r resources/lang/en/ resources/lang/your_code/
```

#### 3. Translate Content
Update the translation values in each file (keep keys unchanged):

**Required Files:**
- `common.php` - UI elements, navigation, actions, form labels
- `auth.php` - Authentication, 2FA, login/logout
- `devices.php` - Device management interface
- `ip_addresses.php` - IP address management
- `ip_address_groups.php` - IP group management  
- `settings.php` - Settings and configuration
- `users.php` - User management
- `widgets.php` - Dashboard widgets and statistics

#### 4. Test Your Translation
- Start the application
- Your language will automatically appear in the language switcher
- Test all major interfaces to ensure translations display correctly
- Check forms, tables, buttons, and error messages

### Translation Guidelines

#### Keys vs Values
- **Never change the keys** (left side of `=>`)
- **Only translate the values** (right side of `=>`)

```php
// ✅ Correct - only translate values
'create' => 'Créer',
'edit' => 'Modifier',

// ❌ Wrong - don't change keys  
'créer' => 'Créer',
'modifier' => 'Modifier',
```

#### Placeholder Variables
Keep placeholder variables unchanged:

```php
// ✅ Correct
'welcome_message' => 'Bienvenue, :name!',
'items_count' => '{0} aucun élément|{1} :count élément|[2,*] :count éléments',

// ❌ Wrong
'welcome_message' => 'Bienvenue, :nom!',  // Don't translate :name
```

#### Context and Consistency
- Consider the context where text appears (button, header, error message)
- Use consistent terminology throughout the application
- Follow your language's UI/software localization conventions
- Keep text concise to fit in UI elements

### Language-Specific Considerations

#### Right-to-Left Languages
For RTL languages (Arabic, Hebrew, etc.), set `'rtl' => true` in the configuration:

```php
'ar' => [
    'code' => 'ar',
    'name' => 'Arabic', 
    'native_name' => 'العربية',
    'flag_emoji' => '🇸🇦',
    'rtl' => true,  // Enable RTL support
],
```

#### Pluralization
Laravel supports complex pluralization rules:

```php
// Simple pluralization
'ip_count' => '{1} IP|[2,*] IPs',

// Complex pluralization (for languages with multiple plural forms)
'device_count' => '{0} geen apparaten|{1} één apparaat|[2,*] :count apparaten',
```

### Testing Your Translation

#### Manual Testing
1. Switch to your language in the navbar
2. Navigate through all major sections:
   - Dashboard and widgets
   - Device management (create, edit, list)
   - IP address management  
   - User management
   - Settings pages
   - Profile page
3. Test form validations and error messages
4. Verify CSV import/export functionality

#### Automated Testing
Run the internationalization tests:

```bash
php artisan test tests/Feature/InternationalizationTest.php
```

### Submitting Your Translation

#### 1. Create a Pull Request
- Fork the repository
- Create a new branch: `feature/add-{language_code}-translation`
- Add your language configuration and translation files
- Commit with descriptive messages

#### 2. Include in Your PR
- **Language code and name** in the PR title
- **Sample screenshots** of key interfaces in your language
- **Translation completeness** - mention any incomplete sections
- **Testing notes** - confirm you've tested major functionality

#### 3. PR Description Template
```markdown
## Add {Language Name} ({language_code}) Translation

### Changes
- Added language configuration for {language_code}
- Translated all core interface elements
- Tested major functionality in new language

### Translation Status
- [x] Common UI elements
- [x] Authentication pages  
- [x] Device management
- [x] IP address management
- [x] User management
- [x] Settings pages
- [x] Dashboard widgets
- [x] Error messages and validations

### Screenshots
[Include 2-3 screenshots of key pages in your language]

### Testing
- [x] Language switching works correctly
- [x] Forms and validations display properly
- [x] Navigation and menus are translated
- [x] No missing translations in main workflows

### Notes
{Any special considerations or incomplete areas}
```

## 🐛 Bug Reports

### Before Reporting
- Search existing issues to avoid duplicates
- Try reproducing the bug in a clean environment
- Check if the issue exists in the latest version

### Bug Report Template
```markdown
## Bug Description
Clear description of what's broken.

## Steps to Reproduce
1. Go to...
2. Click on...
3. See error

## Expected Behavior
What should happen instead.

## Environment
- OpenIPAM version:
- PHP version:
- Laravel version:
- Browser (if UI bug):
- OS:

## Screenshots/Logs
Include relevant screenshots or log entries.
```

## 🚀 Feature Requests

### Feature Request Template
```markdown
## Feature Description
Clear description of the proposed feature.

## Use Case
Why is this feature needed? What problem does it solve?

## Proposed Solution
How you envision this working.

## Alternatives Considered
Other approaches you've considered.

## Additional Context
Screenshots, mockups, or other relevant information.
```

## 💻 Development Setup

### With DevContainer (Development)

1. Open the project in VS Code
2. Install the "Dev Containers" extension
3. Press `F1` and select "Dev Containers: Reopen in Container"
4. The container will be built automatically and the application started
5. The application is available at `http://localhost:8080`
   

### Local Development
```bash
# Clone repository
git clone <repository-url>
cd openipam

# Install dependencies
composer install
npm install && npm run build

# Configure environment
cp .env.example .env
php artisan key:generate

# Migrate database
php artisan migrate

# Create admin user (optional)
php artisan tinker
# In Tinker:
App\Models\User::create([
    'name' => 'Admin',
    'email' => 'admin@example.com',
    'password' => Hash::make('password'),
    'gravatar_type' => 'mp',
    'language' => 'en'
]);

# Start development server
php artisan serve
```

### DevContainer Setup
1. Open project in VS Code
2. Install "Dev Containers" extension
3. Press `F1` → "Dev Containers: Reopen in Container"
4. Everything will be set up automatically

### Running Tests
```bash
# Run all tests
php artisan test

# Run specific test suites
php artisan test tests/Unit/
php artisan test tests/Feature/
php artisan test tests/Feature/InternationalizationTest.php
```

## 📝 Code Style

### PHP Code Style
We use Laravel Pint (PSR-12) for code formatting:

```bash
# Check code style
./vendor/bin/pint --test

# Fix code style
./vendor/bin/pint
```

### Commit Messages
The project uses **[Conventional Commits](https://conventionalcommits.org/)** for automatic versioning:

| Commit Type | Version Bump | Example |
|-------------|--------------|---------|
| `feat:` | Minor (1.0.0 → 1.1.0) | `feat: add mass IP creation` |
| `fix:` | Patch (1.0.0 → 1.0.1) | `fix: resolve IPv6 validation` |
| `perf:` | Patch | `perf: optimize database queries` |
| `security:` | Patch | `security: update dependencies` |
| `docs:` | Patch | `docs: update README` |
| `BREAKING CHANGE:` | Major (1.0.0 → 2.0.0) | `feat!: redesign API` |


**Example Commits:**
```bash
# Features
git commit -m "feat(auth): add email 2FA support"
git commit -m "feat(i18n): add French translation"

# Bug fixes
git commit -m "fix(ui): resolve mobile responsive issues"
git commit -m "fix(csv): handle special characters in import"

# Documentation
git commit -m "docs(readme): update installation instructions"
git commit -m "docs(api): add device management examples"

# Breaking changes
git commit -m "feat!: redesign user authentication

BREAKING CHANGE: Email verification now required for all new users."
```

### Pull Request Process

#### 1. Before Submitting
- [ ] Code follows PSR-12 style (`./vendor/bin/pint`)
- [ ] Tests pass (`php artisan test`)
- [ ] Documentation updated if needed
- [ ] Commit messages follow conventional format

#### 2. PR Description
- Clearly describe the changes
- Reference any related issues
- Include testing instructions
- Add screenshots for UI changes

#### 3. Review Process
- Maintainers will review your PR
- Address any feedback promptly
- Keep your branch up to date with main
- Be patient - reviews may take a few days

## 🏷️ Versioning

OpenIPAM uses [Semantic Versioning](https://semver.org/):

- **MAJOR**: Breaking changes
- **MINOR**: New features (backward compatible)
- **PATCH**: Bug fixes (backward compatible)

Releases are automated using semantic-release based on commit messages.

## 📜 License

By contributing to OpenIPAM, you agree that your contributions will be licensed under the [MIT license](https://opensource.org/license/MIT).

## 🤝 Community Guidelines

### Be Respectful
- Use welcoming and inclusive language
- Respect different viewpoints and experiences
- Accept constructive criticism gracefully
- Focus on what's best for the community

### Be Helpful
- Help newcomers get started
- Share knowledge and resources
- Provide constructive feedback
- Be patient with questions

### Quality Standards
- Test your contributions thoroughly
- Follow coding standards
- Write clear documentation
- Consider the impact on existing users

## 📞 Getting Help

- **Documentation**: Check the README.md first
- **Issues**: Search existing issues before creating new ones
- **Discussions**: Use GitHub Discussions for questions and ideas
- **Email**: Contact maintainers for security issues

## 🎉 Recognition

Contributors are recognized in:
- Release notes for their contributions
- Special thanks in major version releases
- Contributor section (when we create one)

Thank you for contributing to OpenIPAM! 🚀