# CLAUDE.md - LatePoint Gate Codes Plugin Guide

## Development Commands
- Testing: Run WP tests with `wp test` (empty test file awaiting implementation)
- Debug: Set `const DEBUG = true` in main plugin file for verbose logging
- WordPress standards: Run `phpcs --standard=WordPress` for code standards check

## Code Style Guidelines
- Follow WordPress coding standards
- Classes: PascalCase (`LatePoint_Gate_Codes`)
- Functions/variables: snake_case (`display_single_booking_gate_code`)
- Constants: UPPERCASE (`LATEPOINT_GATE_CODES_VERSION`)
- Indent with tabs, 4-space width

## Architectural Patterns
- Singleton pattern for main plugin class
- Direct file access prevention with ABSPATH check
- Proper hook initialization in dedicated method
- Try/catch for error handling with fallback to "#ERR" on failures

## CSS Conventions
- BEM-style naming (os-gate-code, os-gate-code-label)
- Uses LatePoint CSS variables for consistent styling
- Mobile-responsive with media queries

## Security Best Practices
- Escape output with esc_html()
- Validate inputs with proper type checking
- Check dependencies before initializing