# LatePoint Gate Codes - TODO List

This document outlines the planned improvements for the LatePoint Gate Codes plugin in order of priority.

## High Priority

1. **Bug Fixes**
   - Fix redundant date difference condition in `display_single_booking_gate_code()` method
   - Improve error handling when booking date is invalid
   - Ensure consistent translation domain usage

2. **Email Integration**
   - Add gate codes to LatePoint email notifications
   - Create filter hooks to allow customization of email content
   - Ensure gate code displays properly in both HTML and plain text emails

3. **Code Reorganization**
   - Separate gate code generation logic into its own class
   - Move display logic to a separate class
   - Create proper class autoloading

## Medium Priority

4. **Settings Page**
   - Create admin settings page under LatePoint menu
   - Add options for:
     - Gate code format customization
     - Display timing window (currently hardcoded to 2 days)
     - Toggle email reminder message

5. **Enhanced Security** (for other use cases)
   - Implement more secure gate code generation algorithm
   - Add option for time-based code expiration
   - Add proper nonce verification for admin actions

6. **Expanded Documentation**
   - Create developer documentation for hooks and filters
   - Add inline code documentation (PHPDoc)
   - Create user documentation with screenshots

## Lower Priority

7. **Additional Features**
   - Gate code verification system
   - Admin ability to manually regenerate codes

8. **Performance Improvements**
   - Optimize database queries
   - Minify CSS assets

9. **Testing Framework**
   - Create unit tests for code generation logic
   - Set up testing environment

---

Last updated: April 14, 2025
