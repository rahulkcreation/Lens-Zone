# Lens Zone Plugin - Unique CSS Classes Reference

This document lists all unique classes added to each modal screen for independent styling.

## 1. Select Lens Type Screen (Categories)

### Modal Structure
- `.lens-type-modal-header` - Header container
- `.lens-type-modal-title` - Title "Select Lens Type"
- `.lens-type-modal-close` - Close button
- `.lens-type-modal-body` - Body container
- `.lens-type-modal-footer` - Footer container
- `.lens-type-subtotal` - Subtotal display
- `.lens-type-continue-btn` - Continue button
- `.lens-type-empty-message` - Empty state message

### Category Cards
- `.lens-category-card` - Category card container
- `.lens-category-{id}` - Specific category by ID (e.g., `.lens-category-1`)
- `.lens-category-image` - Category image container
- `.lens-category-image-{id}` - Specific category image by ID
- `.lens-category-content` - Category content wrapper
- `.lens-category-title` - Category title
- `.lens-category-description` - Category description

## 2. Select Lens Screen (Sub-Categories)

### Modal Structure
- `.lens-selection-modal-header` - Header container
- `.lens-selection-modal-title` - Title (category name)
- `.lens-selection-modal-close` - Close button
- `.lens-selection-back-btn` - Back button
- `.lens-selection-modal-body` - Body container
- `.lens-selection-modal-footer` - Footer container
- `.lens-selection-subtotal` - Subtotal display
- `.lens-selection-continue-btn` - Continue button

### Sub-Category Cards
- `.lens-subcategory-card` - Sub-category card container
- `.lens-subcategory-{id}` - Specific sub-category by ID
- `.lens-subcategory-image` - Sub-category image container
- `.lens-subcategory-image-{id}` - Specific sub-category image by ID
- `.lens-subcategory-content` - Sub-category content wrapper
- `.lens-subcategory-title` - Sub-category title
- `.lens-subcategory-points` - Points list
- `.lens-subcategory-point-item` - Individual point item
- `.lens-subcategory-view-more` - View more button
- `.lens-subcategory-price` - Price container
- `.lens-subcategory-current-price` - Current price display

## 3. Power Options Screen

### Modal Structure
- `.power-options-modal-header` - Header container
- `.power-options-modal-title` - Title "Add Lens Details"
- `.power-options-modal-close` - Close button
- `.power-options-back-btn` - Back button
- `.power-options-modal-body` - Body container
- `.power-options-section-title` - Section titles
- `.power-options-support-text` - Support text
- `.power-options-form-container` - Form container

### Power Option Cards
- `.power-option-submit-later` - Submit later card
- `.power-option-submit-later-icon` - Submit later icon
- `.power-option-submit-later-content` - Submit later content
- `.power-option-manual` - Manual entry card
- `.power-option-manual-icon` - Manual entry icon
- `.power-option-manual-content` - Manual entry content
- `.power-option-upload` - Upload card
- `.power-option-upload-icon` - Upload icon
- `.power-option-upload-content` - Upload content

## 4. Manual Power Entry Screen

### Modal Structure
- `.manual-power-modal-header` - Header container
- `.manual-power-modal-title` - Title "Submit Eye Power"
- `.manual-power-modal-close` - Close button
- `.manual-power-back-btn` - Back button
- `.manual-power-modal-body` - Body container
- `.manual-power-modal-footer` - Footer container
- `.manual-power-save-btn` - Save & Proceed button
- `.manual-power-section-title` - Section title

### Form Elements
- `.manual-power-checkbox` - Same power checkbox container
- `.manual-power-same-checkbox` - Same power checkbox input
- `.manual-power-same-label` - Same power checkbox label
- `.manual-power-grid` - Power form grid
- `.manual-power-left-column` - Left eye column
- `.manual-power-right-column` - Right eye column
- `.manual-power-eye-title` - Eye title (LEFT/RIGHT)
- `.manual-power-field` - Form field container
- `.manual-power-label` - Field label
- `.manual-power-select` - Select dropdown
- `.manual-power-input` - Input field

### Specific Fields
- `.manual-power-left-sph` - Left SPH select
- `.manual-power-left-cyl` - Left CYL select
- `.manual-power-left-axis` - Left Axis input
- `.manual-power-left-additional` - Left Additional Power select
- `.manual-power-right-sph` - Right SPH select
- `.manual-power-right-cyl` - Right CYL select
- `.manual-power-right-axis` - Right Axis input
- `.manual-power-right-additional` - Right Additional Power select

### User Info
- `.manual-power-user-info` - User info container
- `.manual-power-user-info-title` - User info title
- `.manual-power-user-grid` - User info grid
- `.manual-power-user-name` - Name input
- `.manual-power-user-phone` - Phone input
- `.manual-power-support-text` - Support text

### Login Notice
- `.manual-power-login-notice` - Login notice container
- `.manual-power-login-text` - Login notice text
- `.manual-power-login-btn` - Login/Register button

## 5. Upload Prescription Screen

### Modal Structure
- `.upload-prescription-modal-header` - Header container
- `.upload-prescription-modal-title` - Title "Submit Eye Power"
- `.upload-prescription-modal-close` - Close button
- `.upload-prescription-back-btn` - Back button
- `.upload-prescription-modal-body` - Body container
- `.upload-prescription-modal-footer` - Footer container
- `.upload-prescription-continue-btn` - Continue button
- `.upload-prescription-section-title` - Section title

### Upload Elements
- `.upload-prescription-instructions` - Instructions list
- `.upload-prescription-area` - Upload area container
- `.upload-prescription-icon` - Upload icon
- `.upload-prescription-text` - Upload text
- `.upload-prescription-subtext` - Upload subtext
- `.upload-prescription-file-input` - File input
- `.upload-prescription-preview` - Preview container
- `.upload-prescription-success-msg` - Success message
- `.upload-prescription-filename` - Filename display

### User Info
- `.upload-prescription-user-info` - User info container
- `.upload-prescription-user-info-title` - User info title
- `.upload-prescription-user-grid` - User info grid
- `.upload-prescription-field` - Form field
- `.upload-prescription-label` - Field label
- `.upload-prescription-input` - Input field
- `.upload-prescription-user-name` - Name input
- `.upload-prescription-user-phone` - Phone input

### Login Notice
- `.upload-prescription-login-notice` - Login notice container
- `.upload-prescription-login-text` - Login notice text
- `.upload-prescription-login-btn` - Login/Register button

## Usage Examples

### Example 1: Style only the lens type selection cards
```css
.lens-category-card {
    /* Your custom styles */
}

.lens-category-image {
    width: 120px;
    height: 120px;
}
```

### Example 2: Style only the manual power form
```css
.manual-power-modal-body {
    background: #f5f5f5;
}

.manual-power-select {
    border: 2px solid #blue;
}
```

### Example 3: Style specific category by ID
```css
.lens-category-1 .lens-category-image {
    border: 3px solid red;
}

.lens-category-2 .lens-category-image {
    border: 3px solid blue;
}
```

### Example 4: Mobile-specific styling for lens selection
```css
@media (max-width: 768px) {
    .lens-selection-modal-body .lens-subcategory-card {
        flex-direction: column;
    }
    
    .lens-subcategory-image {
        width: 100px;
        height: 100px;
    }
}
```

## Notes

1. All screens share the same modal container (`#lens-selection-modal`) but have unique classes for their content
2. Each screen's elements can be styled independently without affecting other screens
3. Category and sub-category specific classes include IDs for per-item customization
4. Common elements (like `.power-input`, `.power-select`) are still available for global styling
5. Use the specific classes when you need screen-specific or item-specific styling
