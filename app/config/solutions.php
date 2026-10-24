<?php
/** Static landing content for /solutions/{slug} — ported from ErpSolutionLanding.jsx. */
return [
    'erpnext' => ['name' => 'ERPNext', 'type' => 'ERPNext', 'tagline' => 'Open-source ERP, enterprise-grade implementation',
        'overview' => 'ERPNext is a full-featured open-source ERP by Frappe. We deliver end-to-end implementations — finance, HRMS, payroll, inventory, CRM, manufacturing and asset management — with data migration from legacy systems and UAE-compliant payroll.',
        'features' => ['Finance & Accounting with optional AI insights', 'HRMS & UAE-compliant Payroll Automation', 'Procurement & Inventory Management', 'CRM & Sales Pipeline', 'Asset Management (10,000+ items)', 'Project Management', 'Manufacturing & Production Planning', 'Data migration from legacy systems'],
        'stack' => ['ERPNext', 'Frappe Framework', 'Python', 'MariaDB', 'Redis']],
    'odoo' => ['name' => 'Odoo', 'type' => 'Odoo', 'tagline' => 'Modular business apps that grow with you',
        'overview' => 'Odoo is a suite of integrated business apps covering CRM, eCommerce, accounting, inventory, manufacturing and HR. We customize, integrate and host Odoo to fit your exact workflows — from a single module to a full enterprise rollout.',
        'features' => ['CRM & Sales automation', 'eCommerce & Website builder', 'Accounting & Invoicing', 'Inventory & Warehouse (WMS)', 'Manufacturing (MRP)', 'Human Resources & Payroll', 'Custom module development', 'Third-party integrations & API'],
        'stack' => ['Odoo', 'Python', 'PostgreSQL', 'Custom Modules']],
    'custom-crm' => ['name' => 'Custom CRM', 'type' => 'Custom CRM', 'tagline' => 'A CRM built around your sales process',
        'overview' => "When off-the-shelf CRMs don't fit, we engineer a custom CRM around your exact sales pipeline, automation and reporting needs — with secure role-based access, integrations to your tools, and a clean modern UI your team will actually use.",
        'features' => ['Custom sales pipelines & stages', 'Lead scoring & automation', 'Role-based access control', 'Email & WhatsApp integration', 'Custom dashboards & reporting', 'API integrations with existing tools', 'Mobile-friendly interface', 'Scalable cloud deployment'],
        'stack' => ['React', 'Node.js', 'PostgreSQL', 'REST API']],
    'custom-erp' => ['name' => 'Custom ERP', 'type' => 'Custom ERP', 'tagline' => 'Bespoke ERP engineered from the ground up',
        'overview' => "For organizations whose processes don't map to any packaged ERP, we build a fully bespoke ERP — accounting, inventory, CRM, project management and real-time analytics — compliant with your regional standards and designed to scale with your business.",
        'features' => ['Accounting & Finance (regional compliance)', 'Inventory & Supply Chain', 'CRM & Sales', 'Project Management', 'Real-time analytics & automation', 'Secure, role-based access', 'Scalable & user-friendly', 'Migration from legacy systems'],
        'stack' => ['React', 'Node.js', 'PostgreSQL', 'Cloud Infrastructure']],
];
