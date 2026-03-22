<?php

return [
    'selling_types' => [
        'retail' => 'Retail',
        'wholesale' => 'Wholesale',
    ],

    'barcode_symbologies' => [
        '' => '— Select —',
        'EAN-13' => 'EAN-13',
        'UPC-A' => 'UPC-A',
        'Code128' => 'Code 128',
        'Code39' => 'Code 39',
    ],

    'tax_types' => [
        '' => '— Select —',
        'standard' => 'Standard',
        'exempt' => 'Exempt',
        'zero_rated' => 'Zero rated',
    ],

    'discount_types' => [
        'none' => 'None',
        'percent' => 'Percentage',
        'fixed' => 'Fixed amount',
    ],

    'warranty_terms' => [
        '' => '— None —',
        '30_days' => '30 days',
        '90_days' => '90 days',
        '180_days' => '180 days',
        '1_year' => '1 year',
        '2_years' => '2 years',
    ],

    /**
     * Category => sub-categories (for dependent dropdowns).
     */
    'categories' => [
        'General OTC' => ['Pain relief', 'Cold & flu', 'Digestive', 'Allergy'],
        'Vitamins & supplements' => ['Vitamins', 'Minerals', 'Herbal'],
        'Personal care' => ['Skin', 'Hair', 'Oral'],
        'Prescription' => ['Antibiotics', 'Cardiovascular', 'Diabetes', 'Other Rx'],
        'Medical devices' => ['Monitoring', 'Supports', 'Other'],
    ],
];
