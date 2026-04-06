<?php

declare(strict_types=1);

/*
 * English translations for pipeline templates.
 *
 * Structure mirrors app/Support/PipelineTemplates.php. Stages and tasks are
 * indexed by integer position. PipelineTemplates::all() merges these overrides
 * into the PT defaults when the active locale is "en".
 *
 * Only translatable strings are stored here — color, icon, slug, category,
 * is_won, is_lost, task_type, priority and due_date_offset stay in PHP.
 */

return [
    'categories' => [
        'imobiliaria'        => 'Real Estate',
        'saude'              => 'Health',
        'educacao'           => 'Education',
        'restaurante_food'   => 'Restaurant & Food',
        'ecommerce'          => 'E-commerce',
        'servicos_b2b'       => 'B2B Services',
        'marketing_agencia'  => 'Marketing & Agency',
        'beleza_estetica'    => 'Beauty & Aesthetics',
        'automotivo'         => 'Automotive',
        'advocacia'          => 'Law',
        'tecnologia_saas'    => 'Technology & SaaS',
        'coach_consultoria'  => 'Coach & Consulting',
        'eventos'            => 'Events',
        'construcao_reforma' => 'Construction & Renovation',
        'turismo'            => 'Tourism',
        'fitness'            => 'Fitness',
        'financeiro'         => 'Financial',
        'recursos_humanos'   => 'Human Resources',
        'pet'                => 'Pet',
        'religioso'          => 'Religious',
        'vendas_b2c'         => 'B2C Sales',
    ],

    'templates' => [

        // ── REAL ESTATE ──────────────────────────────────────────────
        'imobiliaria_locacao' => [
            'name' => 'Residential Rental',
            'description' => 'Standard funnel for capturing and closing residential rentals',
            'stages' => [
                ['name' => 'New Lead', 'tasks' => ['Call to qualify interest and budget']],
                ['name' => 'Properties Selected', 'tasks' => ['Send property options via WhatsApp']],
                ['name' => 'Visit Scheduled', 'tasks' => ['Confirm visit 1 day before', 'Visit the property']],
                ['name' => 'Documentation', 'tasks' => ['Request tenant documents', 'Send for credit analysis']],
                ['name' => 'Contract Signed', 'tasks' => []],
                ['name' => 'Not Approved', 'tasks' => []],
            ],
        ],
        'imobiliaria_venda' => [
            'name' => 'Residential Sale',
            'description' => 'Lead capture, financial qualification and closing of property sales',
            'stages' => [
                ['name' => 'New Lead', 'tasks' => ['First contact and profile qualification']],
                ['name' => 'Pre-approved Financing', 'tasks' => ['Request bank simulation']],
                ['name' => 'Visits Done', 'tasks' => ['Schedule and visit properties']],
                ['name' => 'Proposal Presented', 'tasks' => ['Negotiate price and terms']],
                ['name' => 'Financing Approved', 'tasks' => ['Verify complete documentation']],
                ['name' => 'Deed Signed', 'tasks' => []],
                ['name' => 'Lost', 'tasks' => []],
            ],
        ],
        'imobiliaria_lancamento' => [
            'name' => 'Project Pre-launch',
            'description' => 'Lead capture and unit sales during pre-launch of real estate projects',
            'stages' => [
                ['name' => 'Lead Registered', 'tasks' => ['Send project material']],
                ['name' => 'Interest Confirmed', 'tasks' => ['Schedule sales stand visit']],
                ['name' => 'Visit to Stand', 'tasks' => ['Present floor plan and differentials']],
                ['name' => 'Unit Reserved', 'tasks' => ['Collect deposit and data']],
                ['name' => 'Sold', 'tasks' => []],
                ['name' => 'Gave Up', 'tasks' => []],
            ],
        ],
        'imobiliaria_comercial' => [
            'name' => 'Commercial / Warehouse',
            'description' => 'Rental and sale of commercial properties, offices and warehouses',
            'stages' => [
                ['name' => 'New Lead', 'tasks' => ['Understand business needs']],
                ['name' => 'Properties Suggested', 'tasks' => ['Send compatible options']],
                ['name' => 'Technical Inspection', 'tasks' => ['Schedule technical visit']],
                ['name' => 'Negotiation', 'tasks' => ['Negotiate price, term and conditions']],
                ['name' => 'Closed', 'tasks' => []],
                ['name' => 'Lost', 'tasks' => []],
            ],
        ],

        // ── HEALTH ───────────────────────────────────────────────────
        'saude_clinica_medica' => [
            'name' => 'Medical Clinic',
            'description' => 'Lead capture and appointment scheduling for medical clinics',
            'stages' => [
                ['name' => 'Request Received', 'tasks' => ['Confirm specialty and insurance']],
                ['name' => 'Appointment Scheduled', 'tasks' => ['Send appointment reminder']],
                ['name' => 'Appointment Done', 'tasks' => ['NPS satisfaction survey']],
                ['name' => 'In Treatment', 'tasks' => []],
                ['name' => 'No-show', 'tasks' => []],
            ],
        ],
        'saude_odontologia' => [
            'name' => 'Dentistry',
            'description' => 'Lead capture, evaluation and dental treatment funnel',
            'stages' => [
                ['name' => 'New Lead', 'tasks' => ['Qualify treatment desired']],
                ['name' => 'Evaluation Scheduled', 'tasks' => ['Confirm evaluation']],
                ['name' => 'Evaluation Done', 'tasks' => ['Present treatment plan']],
                ['name' => 'Quote Sent', 'tasks' => ['Quote follow-up']],
                ['name' => 'Treatment Started', 'tasks' => []],
                ['name' => 'Did Not Close', 'tasks' => []],
            ],
        ],
        'saude_estetica_spa' => [
            'name' => 'Aesthetics / Spa',
            'description' => 'Capture for aesthetic procedures and spa treatments',
            'stages' => [
                ['name' => 'New Lead', 'tasks' => ['Understand goals and concerns']],
                ['name' => 'Evaluation Scheduled', 'tasks' => ['Confirm evaluation 1 day before']],
                ['name' => 'Protocol Presented', 'tasks' => ['Send personalized quote']],
                ['name' => 'Session Booked', 'tasks' => ['Confirm first session']],
                ['name' => 'Active Client', 'tasks' => []],
                ['name' => 'Did Not Close', 'tasks' => []],
            ],
        ],
        'saude_veterinaria' => [
            'name' => 'Veterinary',
            'description' => 'Pet care, consultations and veterinary treatments',
            'stages' => [
                ['name' => 'New Lead', 'tasks' => ['Collect pet data and type of service']],
                ['name' => 'Appointment Scheduled', 'tasks' => ['Appointment reminder']],
                ['name' => 'Attended', 'tasks' => ['Post-consultation follow-up']],
                ['name' => 'Active Client', 'tasks' => []],
                ['name' => 'Cancelled', 'tasks' => []],
            ],
        ],

        // ── EDUCATION ────────────────────────────────────────────────
        'educacao_idiomas' => [
            'name' => 'Language School',
            'description' => 'Student capture for language schools, with placement and enrollment',
            'stages' => [
                ['name' => 'Interested', 'tasks' => ['Identify language and goal']],
                ['name' => 'Demo Class Scheduled', 'tasks' => ['Confirm demo class']],
                ['name' => 'Demo Class Done', 'tasks' => ['Send class plan and pricing']],
                ['name' => 'In Negotiation', 'tasks' => ['Enrollment follow-up']],
                ['name' => 'Enrolled', 'tasks' => []],
                ['name' => 'Gave Up', 'tasks' => []],
            ],
        ],
        'educacao_cursos_online' => [
            'name' => 'Online Courses',
            'description' => 'Sales funnel for online courses and digital products',
            'stages' => [
                ['name' => 'New Lead', 'tasks' => ['Send warm-up content']],
                ['name' => 'Engaged', 'tasks' => ['Present course offer']],
                ['name' => 'Cart Open', 'tasks' => ['Recover abandoned cart']],
                ['name' => 'Student', 'tasks' => []],
                ['name' => 'Did Not Buy', 'tasks' => []],
            ],
        ],
        'educacao_pre_vestibular' => [
            'name' => 'College Prep',
            'description' => 'Student capture for college and entrance exam prep courses',
            'stages' => [
                ['name' => 'Interested', 'tasks' => ['Collect student data and target course']],
                ['name' => 'Visit Scheduled', 'tasks' => ['Confirm visit to the unit']],
                ['name' => 'Visit Done', 'tasks' => ['Present plan and discounts']],
                ['name' => 'Negotiating', 'tasks' => ['Follow-up with parents/guardians']],
                ['name' => 'Enrolled', 'tasks' => []],
                ['name' => 'Gave Up', 'tasks' => []],
            ],
        ],
        'educacao_pos_graduacao' => [
            'name' => 'Graduate School',
            'description' => 'Lead capture for graduate and MBA programs',
            'stages' => [
                ['name' => 'Lead Registered', 'tasks' => ['Send curriculum and differentials']],
                ['name' => 'Meeting Scheduled', 'tasks' => ['Confirm presentation meeting']],
                ['name' => 'Under Review', 'tasks' => ['Decision follow-up']],
                ['name' => 'Enrolled', 'tasks' => []],
                ['name' => 'Gave Up', 'tasks' => []],
            ],
        ],

        // ── RESTAURANT & FOOD ────────────────────────────────────────
        'restaurante_delivery' => [
            'name' => 'Delivery',
            'description' => 'Customer capture and loyalty for delivery service',
            'stages' => [
                ['name' => 'Order Received', 'tasks' => ['Confirm order and address']],
                ['name' => 'Preparing', 'tasks' => []],
                ['name' => 'Out for Delivery', 'tasks' => []],
                ['name' => 'Delivered', 'tasks' => ['Request NPS rating']],
                ['name' => 'Cancelled', 'tasks' => []],
            ],
        ],
        'restaurante_reservas' => [
            'name' => 'Reservations / Dining',
            'description' => 'Reservations and dining room occupancy management',
            'stages' => [
                ['name' => 'Reservation Request', 'tasks' => ['Confirm table availability']],
                ['name' => 'Reservation Confirmed', 'tasks' => ['Reservation reminder 2h before']],
                ['name' => 'Customer Served', 'tasks' => ['Satisfaction survey']],
                ['name' => 'No-show', 'tasks' => []],
            ],
        ],
        'restaurante_eventos' => [
            'name' => 'Events / Catering',
            'description' => 'Private events, catering and party sales',
            'stages' => [
                ['name' => 'Briefing', 'tasks' => ['Collect event details']],
                ['name' => 'Quote Sent', 'tasks' => ['Quote follow-up']],
                ['name' => 'Venue Visit', 'tasks' => ['Tour the venue']],
                ['name' => 'Deposit Received', 'tasks' => ['Send contract for signature']],
                ['name' => 'Event Held', 'tasks' => []],
                ['name' => 'Cancelled', 'tasks' => []],
            ],
        ],

        // ── E-COMMERCE ───────────────────────────────────────────────
        'ecommerce_recuperacao_carrinho' => [
            'name' => 'Cart Recovery',
            'description' => 'Recovery of abandoned carts in e-commerce',
            'stages' => [
                ['name' => 'Cart Abandoned', 'tasks' => ['First contact (1h after abandonment)']],
                ['name' => 'Response Received', 'tasks' => ['Answer product questions']],
                ['name' => 'Coupon Offered', 'tasks' => ['Track coupon usage']],
                ['name' => 'Purchase Recovered', 'tasks' => []],
                ['name' => 'No Response', 'tasks' => []],
            ],
        ],
        'ecommerce_b2c_geral' => [
            'name' => 'E-commerce B2C',
            'description' => 'Standard B2C e-commerce funnel: question → purchase → after-sales',
            'stages' => [
                ['name' => 'Interested Lead', 'tasks' => ['Answer initial questions']],
                ['name' => 'Product Presented', 'tasks' => ['Send photos and details']],
                ['name' => 'Negotiation', 'tasks' => ['Negotiate price and terms']],
                ['name' => 'Paid', 'tasks' => ['Confirm delivery and satisfaction']],
                ['name' => 'Did Not Buy', 'tasks' => []],
            ],
        ],

        // ── B2B SERVICES ─────────────────────────────────────────────
        'b2b_outbound' => [
            'name' => 'B2B Outbound',
            'description' => 'Active B2B prospecting with BANT qualification',
            'stages' => [
                ['name' => 'Prospect', 'tasks' => ['Research company and decision-maker']],
                ['name' => 'Cold Call', 'tasks' => ['Make first prospecting call']],
                ['name' => 'Meeting Scheduled', 'tasks' => ['Discovery meeting (BANT)']],
                ['name' => 'Proposal Sent', 'tasks' => ['Proposal follow-up']],
                ['name' => 'Negotiation', 'tasks' => ['Final negotiation meeting']],
                ['name' => 'Closed', 'tasks' => []],
                ['name' => 'Lost', 'tasks' => []],
            ],
        ],
        'b2b_inbound' => [
            'name' => 'B2B Inbound',
            'description' => 'Qualification and closing of inbound B2B leads',
            'stages' => [
                ['name' => 'MQL', 'tasks' => ['Qualify incoming lead']],
                ['name' => 'SQL', 'tasks' => ['Schedule product demo']],
                ['name' => 'Demo Done', 'tasks' => ['Send commercial proposal']],
                ['name' => 'Negotiation', 'tasks' => ['Negotiation follow-up']],
                ['name' => 'Customer', 'tasks' => []],
                ['name' => 'Lost', 'tasks' => []],
            ],
        ],

        // ── MARKETING & AGENCY ───────────────────────────────────────
        'agencia_novos_clientes' => [
            'name' => 'Agency — New Clients',
            'description' => 'New client acquisition for marketing agencies',
            'stages' => [
                ['name' => 'New Lead', 'tasks' => ['Qualify size and segment']],
                ['name' => 'Diagnosis', 'tasks' => ['Digital diagnosis meeting']],
                ['name' => 'Proposal', 'tasks' => ['Send personalized proposal']],
                ['name' => 'Negotiation', 'tasks' => ['Follow-up and adjustments']],
                ['name' => 'Active Client', 'tasks' => []],
                ['name' => 'Lost', 'tasks' => []],
            ],
        ],
        'agencia_upsell' => [
            'name' => 'Agency — Upsell',
            'description' => 'Upsell and service expansion for active agency clients',
            'stages' => [
                ['name' => 'Opportunity', 'tasks' => ['Identify gap in current service']],
                ['name' => 'Conversation Started', 'tasks' => ['Schedule review meeting']],
                ['name' => 'Additional Proposal', 'tasks' => ['Present new scope']],
                ['name' => 'Approved', 'tasks' => []],
                ['name' => 'Rejected', 'tasks' => []],
            ],
        ],

        // ── BEAUTY & AESTHETICS ──────────────────────────────────────
        'beleza_salao' => [
            'name' => 'Hair Salon',
            'description' => 'Customer capture and loyalty for beauty salon',
            'stages' => [
                ['name' => 'New Lead', 'tasks' => ['Identify desired service']],
                ['name' => 'Booked', 'tasks' => ['Confirm time 1 day before']],
                ['name' => 'Attended', 'tasks' => ['Satisfaction survey']],
                ['name' => 'No-show', 'tasks' => []],
            ],
        ],
        'beleza_barbearia' => [
            'name' => 'Barbershop',
            'description' => 'Bookings and loyalty for barbershop',
            'stages' => [
                ['name' => 'New Lead', 'tasks' => ['Identify desired service']],
                ['name' => 'Booked', 'tasks' => ['Appointment reminder']],
                ['name' => 'Attended', 'tasks' => ['Invite to loyalty program']],
                ['name' => 'No-show', 'tasks' => []],
            ],
        ],
        'beleza_estetica_avancada' => [
            'name' => 'Advanced Aesthetics',
            'description' => 'Advanced aesthetic procedures (botox, fillers, peeling)',
            'stages' => [
                ['name' => 'New Lead', 'tasks' => ['Understand client goals']],
                ['name' => 'Evaluation Scheduled', 'tasks' => ['Confirm evaluation']],
                ['name' => 'Plan Presented', 'tasks' => ['Send detailed quote']],
                ['name' => 'Procedure Booked', 'tasks' => ['Pre-procedure instructions']],
                ['name' => 'Client', 'tasks' => []],
                ['name' => 'Did Not Close', 'tasks' => []],
            ],
        ],

        // ── AUTOMOTIVE ───────────────────────────────────────────────
        'automotivo_concessionaria' => [
            'name' => 'Dealership',
            'description' => 'New vehicle sales at dealership',
            'stages' => [
                ['name' => 'New Lead', 'tasks' => ['Qualify model interest']],
                ['name' => 'Test Drive Scheduled', 'tasks' => ['Confirm test drive']],
                ['name' => 'Test Drive Done', 'tasks' => ['Present commercial proposal']],
                ['name' => 'In Negotiation', 'tasks' => ['Financing simulation']],
                ['name' => 'Sold', 'tasks' => []],
                ['name' => 'Lost', 'tasks' => []],
            ],
        ],
        'automotivo_oficina' => [
            'name' => 'Mechanic Shop',
            'description' => 'Vehicle service and maintenance at workshop',
            'stages' => [
                ['name' => 'Request', 'tasks' => ['Collect vehicle data and issue']],
                ['name' => 'Diagnosis', 'tasks' => ['Vehicle inspection']],
                ['name' => 'Quote Approved', 'tasks' => ['Start repair']],
                ['name' => 'Completed', 'tasks' => ['Satisfaction survey']],
                ['name' => 'Not Approved', 'tasks' => []],
            ],
        ],

        // ── LAW ──────────────────────────────────────────────────────
        'advocacia_consulta' => [
            'name' => 'Initial Consultation',
            'description' => 'Lead capture and qualification for legal consultations',
            'stages' => [
                ['name' => 'Contact Received', 'tasks' => ['Initial case screening']],
                ['name' => 'Consultation Scheduled', 'tasks' => ['Confirm meeting with attorney']],
                ['name' => 'Case Analysis', 'tasks' => ['Study submitted documents']],
                ['name' => 'Fee Proposal', 'tasks' => ['Present contract']],
                ['name' => 'Client', 'tasks' => []],
                ['name' => 'Did Not Hire', 'tasks' => []],
            ],
        ],
        'advocacia_trabalhista' => [
            'name' => 'Labor Cases',
            'description' => 'Capture and management of labor lawsuits',
            'stages' => [
                ['name' => 'New Lead', 'tasks' => ['Collect case history']],
                ['name' => 'Feasibility Analysis', 'tasks' => ['Calculate amounts owed']],
                ['name' => 'Contract Sent', 'tasks' => ['Signature follow-up']],
                ['name' => 'Case Filed', 'tasks' => []],
                ['name' => 'Withdrawal', 'tasks' => []],
            ],
        ],

        // ── TECHNOLOGY & SAAS ────────────────────────────────────────
        'saas_trial' => [
            'name' => 'SaaS — Trial → Paid',
            'description' => 'Conversion from free trial to paid subscription',
            'stages' => [
                ['name' => 'Trial Started', 'tasks' => ['Initial onboarding']],
                ['name' => 'Engaged', 'tasks' => ['Customer success session']],
                ['name' => 'Trial Expiring', 'tasks' => ['Present paid plans']],
                ['name' => 'Paying Customer', 'tasks' => []],
                ['name' => 'Churned', 'tasks' => []],
            ],
        ],
        'saas_enterprise' => [
            'name' => 'SaaS Enterprise',
            'description' => 'Enterprise SaaS sales with long cycle and multiple stakeholders',
            'stages' => [
                ['name' => 'Qualified Lead', 'tasks' => ['Discovery call with decision-maker']],
                ['name' => 'Custom Demo', 'tasks' => ['Run customized demo']],
                ['name' => 'POC / Trial', 'tasks' => ['Track POC usage']],
                ['name' => 'Proposal', 'tasks' => ['Send commercial proposal']],
                ['name' => 'Legal Negotiation', 'tasks' => ['Review contract with legal']],
                ['name' => 'Customer', 'tasks' => []],
                ['name' => 'Lost', 'tasks' => []],
            ],
        ],

        // ── COACH & CONSULTING ───────────────────────────────────────
        'coach_captacao' => [
            'name' => 'Coach — Acquisition',
            'description' => 'New client acquisition for mentorship/coaching',
            'stages' => [
                ['name' => 'New Lead', 'tasks' => ['Free diagnostic session']],
                ['name' => 'Strategy Call', 'tasks' => ['Run strategy session']],
                ['name' => 'Proposal Presented', 'tasks' => ['Proposal follow-up']],
                ['name' => 'Mentee', 'tasks' => []],
                ['name' => 'Did Not Close', 'tasks' => []],
            ],
        ],
        'consultoria_diagnostico' => [
            'name' => 'Business Consulting',
            'description' => 'Diagnosis and sale of business consulting projects',
            'stages' => [
                ['name' => 'New Lead', 'tasks' => ['Exploratory meeting']],
                ['name' => 'Diagnosis Done', 'tasks' => ['Prepare diagnosis report']],
                ['name' => 'Proposal', 'tasks' => ['Present project']],
                ['name' => 'Negotiation', 'tasks' => ['Adjust scope and pricing']],
                ['name' => 'Project Started', 'tasks' => []],
                ['name' => 'Did Not Close', 'tasks' => []],
            ],
        ],

        // ── EVENTS ───────────────────────────────────────────────────
        'eventos_casamentos' => [
            'name' => 'Weddings',
            'description' => 'Capture and sale of wedding packages',
            'stages' => [
                ['name' => 'New Lead', 'tasks' => ['Collect date and event style']],
                ['name' => 'Initial Meeting', 'tasks' => ['Present portfolio and packages']],
                ['name' => 'Venue Visit', 'tasks' => ['Tour the chosen venue']],
                ['name' => 'Proposal', 'tasks' => ['Send detailed quote']],
                ['name' => 'Booked', 'tasks' => []],
                ['name' => 'Did Not Close', 'tasks' => []],
            ],
        ],
        'eventos_corporativos' => [
            'name' => 'Corporate Events',
            'description' => 'Corporate event, convention and workshop sales',
            'stages' => [
                ['name' => 'Briefing Received', 'tasks' => ['Understand goal and headcount']],
                ['name' => 'Technical Proposal', 'tasks' => ['Send initial proposal']],
                ['name' => 'Site Visit', 'tasks' => ['Visit venue with client']],
                ['name' => 'Contract', 'tasks' => ['Negotiate and sign contract']],
                ['name' => 'Held', 'tasks' => []],
                ['name' => 'Cancelled', 'tasks' => []],
            ],
        ],
        'eventos_infantis' => [
            'name' => 'Kids Birthdays',
            'description' => 'Kids parties, decoration and organization',
            'stages' => [
                ['name' => 'New Lead', 'tasks' => ['Collect theme and child age']],
                ['name' => 'Quote', 'tasks' => ['Send personalized quote']],
                ['name' => 'Deposit Paid', 'tasks' => ['Confirm vendors and schedule']],
                ['name' => 'Party Held', 'tasks' => []],
                ['name' => 'Cancelled', 'tasks' => []],
            ],
        ],

        // ── CONSTRUCTION & RENOVATION ────────────────────────────────
        'construcao_obra_residencial' => [
            'name' => 'Residential Construction',
            'description' => 'Building houses and residences from scratch',
            'stages' => [
                ['name' => 'New Lead', 'tasks' => ['Understand scope and location']],
                ['name' => 'Site Visit', 'tasks' => ['Technical visit to the lot']],
                ['name' => 'Project', 'tasks' => ['Develop architectural project']],
                ['name' => 'Quote', 'tasks' => ['Present detailed quote']],
                ['name' => 'Contract', 'tasks' => ['Sign contract and schedule']],
                ['name' => 'Construction Started', 'tasks' => []],
                ['name' => 'Cancelled', 'tasks' => []],
            ],
        ],
        'construcao_reforma' => [
            'name' => 'Apartment Renovation',
            'description' => 'Residential and commercial renovations',
            'stages' => [
                ['name' => 'New Lead', 'tasks' => ['Collect renovation scope']],
                ['name' => 'Technical Visit', 'tasks' => ['On-site measurement']],
                ['name' => 'Quote Sent', 'tasks' => ['Approval follow-up']],
                ['name' => 'Hired', 'tasks' => []],
                ['name' => 'Did Not Close', 'tasks' => []],
            ],
        ],
        'construcao_materiais' => [
            'name' => 'Building Supplies Store',
            'description' => 'Sales at construction materials store',
            'stages' => [
                ['name' => 'Quote Requested', 'tasks' => ['Check stock for items']],
                ['name' => 'Quote Sent', 'tasks' => ['Send detailed quote']],
                ['name' => 'Negotiation', 'tasks' => ['Follow-up and price adjustments']],
                ['name' => 'Paid', 'tasks' => []],
                ['name' => 'Lost', 'tasks' => []],
            ],
        ],

        // ── TOURISM ──────────────────────────────────────────────────
        'turismo_pacotes' => [
            'name' => 'Travel Packages',
            'description' => 'Travel agency package sales',
            'stages' => [
                ['name' => 'New Lead', 'tasks' => ['Understand destination, dates and profile']],
                ['name' => 'Quote Sent', 'tasks' => ['Send package options']],
                ['name' => 'Negotiation', 'tasks' => ['Follow-up and adjustments']],
                ['name' => 'Booked', 'tasks' => []],
                ['name' => 'Did Not Close', 'tasks' => []],
            ],
        ],
        'turismo_hospedagem' => [
            'name' => 'Lodging',
            'description' => 'Bookings for inns, hotels and resorts',
            'stages' => [
                ['name' => 'Request', 'tasks' => ['Check date availability']],
                ['name' => 'Rate Sent', 'tasks' => ['Send rates and conditions']],
                ['name' => 'Booking Confirmed', 'tasks' => ['Pre check-in reminder']],
                ['name' => 'Cancelled', 'tasks' => []],
            ],
        ],

        // ── FITNESS ──────────────────────────────────────────────────
        'fitness_academia' => [
            'name' => 'Gym',
            'description' => 'New member acquisition for gym',
            'stages' => [
                ['name' => 'New Lead', 'tasks' => ['Invite to trial class']],
                ['name' => 'Trial Class', 'tasks' => ['Confirm trial class']],
                ['name' => 'Physical Assessment', 'tasks' => ['Present plans and discounts']],
                ['name' => 'Enrolled', 'tasks' => []],
                ['name' => 'Gave Up', 'tasks' => []],
            ],
        ],
        'fitness_personal' => [
            'name' => 'Personal Trainer',
            'description' => 'Client capture for personal trainer',
            'stages' => [
                ['name' => 'New Lead', 'tasks' => ['Understand goals and availability']],
                ['name' => 'Initial Assessment', 'tasks' => ['Run physical assessment']],
                ['name' => 'Plan Presented', 'tasks' => ['Plan follow-up']],
                ['name' => 'Active Client', 'tasks' => []],
                ['name' => 'Did Not Close', 'tasks' => []],
            ],
        ],

        // ── FINANCIAL ────────────────────────────────────────────────
        'financeiro_consignado' => [
            'name' => 'Payroll Loan',
            'description' => 'Capture and contracting of payroll-deductible credit',
            'stages' => [
                ['name' => 'New Lead', 'tasks' => ['Qualify benefit type and amount']],
                ['name' => 'Simulation', 'tasks' => ['Present simulation']],
                ['name' => 'Documents', 'tasks' => ['Collect documents']],
                ['name' => 'Contracted', 'tasks' => []],
                ['name' => 'Not Approved', 'tasks' => []],
            ],
        ],
        'financeiro_investimentos' => [
            'name' => 'Investments',
            'description' => 'Investor capture for financial advisory',
            'stages' => [
                ['name' => 'New Lead', 'tasks' => ['Qualify profile and assets']],
                ['name' => 'Initial Meeting', 'tasks' => ['Presentation meeting']],
                ['name' => 'Suitability', 'tasks' => ['Run profile analysis']],
                ['name' => 'Portfolio Suggested', 'tasks' => ['Present recommended portfolio']],
                ['name' => 'Active Client', 'tasks' => []],
                ['name' => 'Did Not Close', 'tasks' => []],
            ],
        ],
        'financeiro_emprestimo' => [
            'name' => 'Personal Loan',
            'description' => 'Capture and contracting of personal loans',
            'stages' => [
                ['name' => 'New Lead', 'tasks' => ['Qualify desired amount and income']],
                ['name' => 'Credit Analysis', 'tasks' => ['Submit for analysis']],
                ['name' => 'Approved', 'tasks' => ['Present terms']],
                ['name' => 'Contracted', 'tasks' => []],
                ['name' => 'Rejected', 'tasks' => []],
            ],
        ],

        // ── HUMAN RESOURCES ──────────────────────────────────────────
        'rh_recrutamento' => [
            'name' => 'Recruiting',
            'description' => 'Recruitment and candidate selection pipeline',
            'stages' => [
                ['name' => 'Candidates', 'tasks' => ['Resume screening']],
                ['name' => 'HR Interview', 'tasks' => ['Run initial interview']],
                ['name' => 'Technical Interview', 'tasks' => ['Technical evaluation with manager']],
                ['name' => 'Offer', 'tasks' => ['Send salary offer']],
                ['name' => 'Hired', 'tasks' => []],
                ['name' => 'Not Approved', 'tasks' => []],
            ],
        ],

        // ── PET ──────────────────────────────────────────────────────
        'pet_banho_tosa' => [
            'name' => 'Bath & Grooming',
            'description' => 'Pet shop bookings and loyalty',
            'stages' => [
                ['name' => 'Request', 'tasks' => ['Collect pet and service data']],
                ['name' => 'Booked', 'tasks' => ['Confirm time']],
                ['name' => 'Attended', 'tasks' => ['Satisfaction survey']],
                ['name' => 'No-show', 'tasks' => []],
            ],
        ],
        'pet_adestramento' => [
            'name' => 'Pet Training',
            'description' => 'Capture for dog training services',
            'stages' => [
                ['name' => 'New Lead', 'tasks' => ['Understand pet behaviors']],
                ['name' => 'Visit Scheduled', 'tasks' => ['Assessment at client home']],
                ['name' => 'Plan Presented', 'tasks' => ['Plan follow-up']],
                ['name' => 'Active Client', 'tasks' => []],
                ['name' => 'Did Not Close', 'tasks' => []],
            ],
        ],

        // ── RELIGIOUS ────────────────────────────────────────────────
        'religioso_doacoes' => [
            'name' => 'Recurring Donations',
            'description' => 'Recurring donor capture for church or NGO',
            'stages' => [
                ['name' => 'Interested', 'tasks' => ['Present institution projects']],
                ['name' => 'Conversation Started', 'tasks' => ['Share testimonials']],
                ['name' => 'Donation Confirmed', 'tasks' => ['Send personalized thank you']],
                ['name' => 'Did Not Donate', 'tasks' => []],
            ],
        ],

        // ── B2C SALES ────────────────────────────────────────────────
        'b2c_whatsapp_commerce' => [
            'name' => 'WhatsApp Commerce',
            'description' => 'Direct sales via WhatsApp',
            'stages' => [
                ['name' => 'Message Received', 'tasks' => ['Reply quickly']],
                ['name' => 'Product Presented', 'tasks' => ['Send photos and videos']],
                ['name' => 'Negotiation', 'tasks' => ['Negotiate price and payment']],
                ['name' => 'Paid', 'tasks' => []],
                ['name' => 'Did Not Buy', 'tasks' => []],
            ],
        ],
        'b2c_loja_fisica' => [
            'name' => 'Physical Store',
            'description' => 'Service and sales at physical showroom',
            'stages' => [
                ['name' => 'Visitor', 'tasks' => ['Approach and qualify interest']],
                ['name' => 'Demo', 'tasks' => ['Present products']],
                ['name' => 'In Negotiation', 'tasks' => ['Follow-up if did not close']],
                ['name' => 'Sold', 'tasks' => []],
                ['name' => 'Did Not Buy', 'tasks' => []],
            ],
        ],

        // ── TECHNOLOGY — others ──────────────────────────────────────
        'tech_manutencao_pc' => [
            'name' => 'PC/Laptop Repair',
            'description' => 'Computer repair shop service',
            'stages' => [
                ['name' => 'Request', 'tasks' => ['Diagnose reported problem']],
                ['name' => 'Equipment Received', 'tasks' => ['Detailed technical analysis']],
                ['name' => 'Quote', 'tasks' => ['Present quote to customer']],
                ['name' => 'Repaired', 'tasks' => ['Notify customer for pickup']],
                ['name' => 'Not Approved', 'tasks' => []],
            ],
        ],
        'tech_dev_sob_demanda' => [
            'name' => 'Custom Dev',
            'description' => 'Software development project sales',
            'stages' => [
                ['name' => 'New Lead', 'tasks' => ['Initial briefing']],
                ['name' => 'Discovery', 'tasks' => ['Technical scoping meeting']],
                ['name' => 'Technical Proposal', 'tasks' => ['Draft scope and quote']],
                ['name' => 'Negotiation', 'tasks' => ['Adjust scope and timeline']],
                ['name' => 'Project Started', 'tasks' => []],
                ['name' => 'Lost', 'tasks' => []],
            ],
        ],

        // ── DIGITAL MARKETING ────────────────────────────────────────
        'marketing_trafego_pago' => [
            'name' => 'Paid Traffic',
            'description' => 'Sales of Meta/Google paid traffic management',
            'stages' => [
                ['name' => 'New Lead', 'tasks' => ['Qualify budget and market']],
                ['name' => 'Account Audit', 'tasks' => ['Audit current accounts']],
                ['name' => 'Proposal', 'tasks' => ['Present management plan']],
                ['name' => 'Active Client', 'tasks' => []],
                ['name' => 'Lost', 'tasks' => []],
            ],
        ],
        'marketing_social_media' => [
            'name' => 'Social Media',
            'description' => 'Monthly social media management sales',
            'stages' => [
                ['name' => 'New Lead', 'tasks' => ['Understand brand goals']],
                ['name' => 'Diagnosis', 'tasks' => ['Analysis of current social channels']],
                ['name' => 'Proposal', 'tasks' => ['Send monthly plan']],
                ['name' => 'Active Client', 'tasks' => []],
                ['name' => 'Lost', 'tasks' => []],
            ],
        ],
    ],
];
