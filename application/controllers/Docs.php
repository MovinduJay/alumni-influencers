<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Docs Controller
 *
 * Serves the Swagger/OpenAPI documentation UI for the public API.
 */
class Docs extends CI_Controller
{
    public function index()
    {
        $data = array(
            'title' => 'API Documentation',
            'base_url' => base_url()
        );

        $this->load->view('api/docs', $data);
    }

    public function spec()
    {
        $spec = array(
            'openapi' => '3.0.3',
            'info' => array(
                'title' => 'Alumni Influencers Platform API',
                'description' => 'REST API for public alumni resources, analytics dashboard intelligence, session-authenticated self-service endpoints, bidding workflows, and admin API-client management. Scopes include read:alumni, read:analytics, read:donations, read:alumni_of_day, and write:alumni.',
                'version' => '2.0.0'
            ),
            'servers' => array(
                array(
                    'url' => base_url('api/v1'),
                    'description' => 'API v1'
                )
            ),
            'components' => array(
                'securitySchemes' => array(
                    'BearerAuth' => array(
                        'type' => 'http',
                        'scheme' => 'bearer',
                        'bearerFormat' => 'API Token'
                    ),
                    'CookieSession' => array(
                        'type' => 'apiKey',
                        'in' => 'cookie',
                        'name' => 'ci_session',
                        'description' => 'Session cookie created by /api/v1/auth/login.'
                    )
                ),
                'schemas' => array(
                    'Error' => array(
                        'type' => 'object',
                        'properties' => array(
                            'error' => array('type' => 'string', 'example' => 'Validation failed'),
                            'message' => array('type' => 'string', 'example' => 'amount must be greater than 0.'),
                            'required_scope' => array('type' => 'string', 'example' => 'alumni:read')
                        )
                    ),
                    'AlumniCreate' => array(
                        'type' => 'object',
                        'required' => array('email', 'password', 'first_name', 'last_name'),
                        'properties' => array(
                            'email' => array('type' => 'string', 'format' => 'email'),
                            'password' => array('type' => 'string', 'format' => 'password'),
                            'first_name' => array('type' => 'string'),
                            'last_name' => array('type' => 'string'),
                            'bio' => array('type' => 'string'),
                            'linkedin_url' => array('type' => 'string', 'format' => 'uri')
                        )
                    ),
                    'AlumniPatch' => array(
                        'type' => 'object',
                        'properties' => array(
                            'first_name' => array('type' => 'string'),
                            'last_name' => array('type' => 'string'),
                            'bio' => array('type' => 'string'),
                            'linkedin_url' => array('type' => 'string', 'format' => 'uri')
                        )
                    ),
                    'LoginInput' => array(
                        'type' => 'object',
                        'required' => array('email', 'password'),
                        'properties' => array(
                            'email' => array('type' => 'string', 'format' => 'email'),
                            'password' => array('type' => 'string', 'format' => 'password')
                        )
                    ),
                    'TokenInput' => array(
                        'type' => 'object',
                        'required' => array('token'),
                        'properties' => array('token' => array('type' => 'string'))
                    ),
                    'ForgotPasswordInput' => array(
                        'type' => 'object',
                        'required' => array('email'),
                        'properties' => array('email' => array('type' => 'string', 'format' => 'email'))
                    ),
                    'ResetPasswordInput' => array(
                        'type' => 'object',
                        'required' => array('token', 'password', 'confirm_password'),
                        'properties' => array(
                            'token' => array('type' => 'string'),
                            'password' => array('type' => 'string', 'format' => 'password'),
                            'confirm_password' => array('type' => 'string', 'format' => 'password')
                        )
                    ),
                    'DegreeInput' => $this->recordSchema(array('title', 'institution')),
                    'CertificationInput' => $this->recordSchema(array('title', 'issuer')),
                    'LicenceInput' => $this->recordSchema(array('title', 'awarding_body')),
                    'CourseInput' => $this->recordSchema(array('title', 'provider')),
                    'EmploymentInput' => array(
                        'type' => 'object',
                        'required' => array('company', 'position', 'start_date'),
                        'properties' => array(
                            'company' => array('type' => 'string'),
                            'position' => array('type' => 'string'),
                            'start_date' => array('type' => 'string', 'format' => 'date'),
                            'end_date' => array('type' => 'string', 'format' => 'date', 'nullable' => TRUE)
                        )
                    ),
                    'BidCreate' => array(
                        'type' => 'object',
                        'required' => array('amount', 'bid_date'),
                        'properties' => array(
                            'amount' => array('type' => 'number', 'format' => 'float'),
                            'bid_date' => array('type' => 'string', 'format' => 'date')
                        )
                    ),
                    'BidUpdate' => array(
                        'type' => 'object',
                        'required' => array('amount'),
                        'properties' => array(
                            'amount' => array('type' => 'number', 'format' => 'float')
                        )
                    ),
                    'SponsorshipCreate' => array(
                        'type' => 'object',
                        'required' => array('sponsor_name', 'amount_offered', 'status'),
                        'properties' => array(
                            'sponsor_name' => array('type' => 'string'),
                            'amount_offered' => array('type' => 'number', 'format' => 'float'),
                            'status' => array('type' => 'string', 'enum' => array('pending', 'accepted', 'rejected'))
                        )
                    ),
                    'SponsorshipStatusUpdate' => array(
                        'type' => 'object',
                        'required' => array('status'),
                        'properties' => array(
                            'status' => array('type' => 'string', 'enum' => array('pending', 'accepted', 'rejected'))
                        )
                    ),
                    'EventCreate' => array(
                        'type' => 'object',
                        'required' => array('event_name', 'event_date'),
                        'properties' => array(
                            'event_name' => array('type' => 'string'),
                            'event_date' => array('type' => 'string', 'format' => 'date')
                        )
                    ),
                    'ApiClientCreate' => array(
                        'type' => 'object',
                        'required' => array('client_name'),
                        'properties' => array(
                            'client_name' => array('type' => 'string'),
                            'scope' => array('type' => 'string', 'example' => 'read:alumni,read:analytics')
                        )
                    ),
                    'ApiClientStatusUpdate' => array(
                        'type' => 'object',
                        'required' => array('is_active'),
                        'properties' => array(
                            'is_active' => array('type' => 'boolean')
                        )
                    ),
                    'WinnerSelectionInput' => array(
                        'type' => 'object',
                        'properties' => array(
                            'featured_date' => array('type' => 'string', 'format' => 'date', 'example' => '2026-04-30')
                        )
                    ),
                    'AnalyticsFilters' => array(
                        'type' => 'object',
                        'properties' => array(
                            'programme_id' => array('type' => 'integer'),
                            'industry_sector_id' => array('type' => 'integer'),
                            'skill_id' => array('type' => 'integer'),
                            'graduation_from' => array('type' => 'string', 'format' => 'date'),
                            'graduation_to' => array('type' => 'string', 'format' => 'date'),
                            'keyword' => array('type' => 'string')
                        )
                    )
                )
            ),
            'security' => array(
                array('BearerAuth' => array())
            ),
            'paths' => array_merge(
                $this->authPaths(),
                $this->publicPaths(),
                $this->sessionPaths(),
                $this->adminPaths()
            )
        );

        $this->output
            ->set_content_type('application/json')
            ->set_output(json_encode($spec, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
    }

    private function authPaths()
    {
        return array(
            '/auth/register' => array(
                'post' => $this->writeOp('Authentication', 'Register a university-domain alumni account', '#/components/schemas/AlumniCreate', array('status' => 'created', 'message' => 'Registration successful. Please verify your email.'), array(), FALSE)
            ),
            '/auth/verify' => array(
                'post' => $this->writeOp('Authentication', 'Verify an email address using the emailed token', '#/components/schemas/TokenInput', array('status' => 'success', 'message' => 'Email verified successfully.'), array(), FALSE, FALSE)
            ),
            '/auth/forgot-password' => array(
                'post' => $this->writeOp('Authentication', 'Request a password reset email', '#/components/schemas/ForgotPasswordInput', array('status' => 'success', 'message' => 'If an account with that email exists, a password reset link has been sent.'), array(), FALSE, FALSE)
            ),
            '/auth/reset-password' => array(
                'post' => $this->writeOp('Authentication', 'Reset password using an emailed token', '#/components/schemas/ResetPasswordInput', array('status' => 'success', 'message' => 'Password reset successfully.'), array(), FALSE, FALSE)
            ),
            '/auth/login' => array(
                'post' => $this->writeOp('Authentication', 'Create an authenticated alumni session', '#/components/schemas/LoginInput', array('status' => 'success', 'message' => 'Login successful.', 'session' => array('logged_in' => TRUE)), array(), FALSE, FALSE)
            ),
            '/auth/me' => array(
                'get' => $this->simpleOp('Authentication', 'Return the current session user', 'success', array('user' => array('id' => 1, 'email' => 'alumni@westminster.ac.uk')), array('CookieSession' => array()))
            ),
            '/auth/logout' => array(
                'post' => $this->simpleOp('Authentication', 'Destroy the current session', 'success', array('message' => 'Logout successful.'), array('CookieSession' => array()))
            )
        );
    }

    private function publicPaths()
    {
        return array(
            '/featured/today' => array('get' => $this->simpleOp('Legacy', 'Legacy alias for today\'s featured alumnus', 'success', array('featured' => array('featured_date' => '2026-04-06', 'alumni_id' => 1)), array('BearerAuth' => array()))),
            '/featured' => array('get' => $this->simpleOp('Legacy', 'Legacy alias for featured alumni collection', 'success', array('featured' => array(array('featured_date' => '2026-04-06', 'alumni_id' => 1))), array('BearerAuth' => array()))),
            '/featured-alumni' => array('get' => $this->simpleOp('Featured Alumni', 'List featured alumni resources', 'success', array('featured_alumni' => array(array('featured_date' => '2026-04-06', 'alumni_id' => 1))), array('BearerAuth' => array()))),
            '/featured-alumni/{date}' => array('get' => $this->simpleOp('Featured Alumni', 'Get a featured alumni resource by date', 'success', array('featured_alumnus' => array('featured_date' => '2026-04-06', 'alumni_id' => 1)), array('BearerAuth' => array()), TRUE)),
            '/alumni' => array(
                'get' => $this->simpleOp('Alumni', 'List alumni resources', 'success', array('alumni' => array(array('id' => 1, 'first_name' => 'John', 'last_name' => 'Smith'))), array('BearerAuth' => array())),
                'post' => $this->writeOp('Alumni', 'Create an alumni resource', '#/components/schemas/AlumniCreate', array('status' => 'created', 'message' => 'Alumni resource created successfully.', 'alumni' => array('id' => 5, 'email' => 'jane.doe@westminster.ac.uk')), array('BearerAuth' => array()))
            ),
            '/alumni/{id}' => array(
                'get' => $this->simpleOp('Alumni', 'Get alumni profile', 'success', array('alumnus' => array('id' => 1, 'first_name' => 'John', 'last_name' => 'Smith')), array('BearerAuth' => array()), TRUE),
                'patch' => $this->writeOp('Alumni', 'Partially update an alumni resource', '#/components/schemas/AlumniPatch', array('status' => 'success', 'message' => 'Alumni updated successfully.', 'alumni' => array('id' => 1)), array('BearerAuth' => array()), TRUE),
                'delete' => $this->deleteOp('Alumni', 'Soft-delete an alumni resource', array('BearerAuth' => array()), TRUE)
            ),
            '/analytics/options' => array(
                'get' => $this->simpleOp('Analytics', 'List programmes, industry sectors, and skills for dashboard filters. Requires read:analytics.', 'success', array('options' => array('programmes' => array(), 'industry_sectors' => array(), 'skills' => array())), array('BearerAuth' => array()))
            ),
            '/analytics/overview' => array(
                'get' => $this->simpleOp('Analytics', 'Return chart-ready dashboard data from the database. Requires read:analytics.', 'success', array('analytics' => array('summary' => array('alumni_count' => 10), 'charts' => array(), 'insights' => array())), array('BearerAuth' => array()))
            ),
            '/analytics/alumni' => array(
                'get' => $this->simpleOp('Analytics', 'Return filtered alumni rows for tables and exports. Requires read:alumni.', 'success', array('alumni' => array()), array('BearerAuth' => array()))
            ),
            '/donations/summary' => array(
                'get' => $this->simpleOp('Donations', 'Return sponsorship funding totals by status. Requires read:donations.', 'success', array('donations' => array(array('status' => 'accepted', 'total_amount' => 450))), array('BearerAuth' => array()))
            )
        );
    }

    private function sessionPaths()
    {
        return array(
            '/me/profile' => array(
                'get' => $this->simpleOp('My Profile', 'Get the authenticated alumni profile', 'success', array('profile' => array('alumni' => array('id' => 1), 'degrees' => array())), array('CookieSession' => array())),
                'patch' => $this->writeOp('My Profile', 'Update the authenticated alumni profile', '#/components/schemas/AlumniPatch', array('status' => 'success', 'message' => 'Profile updated successfully.', 'profile' => array('alumni' => array('id' => 1))), array('CookieSession' => array()))
            ),
            '/me/profile/image' => array('post' => $this->uploadOp()),
            '/me/degrees' => $this->crudCollection('My Profile', 'degree', 'degrees', '#/components/schemas/DegreeInput', array('title' => 'BSc Computer Science', 'institution' => 'University of Westminster')),
            '/me/degrees/{id}' => $this->crudItem('My Profile', 'degree', '#/components/schemas/DegreeInput'),
            '/me/certifications' => $this->crudCollection('My Profile', 'certification', 'certifications', '#/components/schemas/CertificationInput', array('title' => 'AWS Certified Developer', 'issuer' => 'Amazon Web Services')),
            '/me/certifications/{id}' => $this->crudItem('My Profile', 'certification', '#/components/schemas/CertificationInput'),
            '/me/licences' => $this->crudCollection('My Profile', 'licence', 'licences', '#/components/schemas/LicenceInput', array('title' => 'Professional Licence', 'awarding_body' => 'Licensing Board')),
            '/me/licences/{id}' => $this->crudItem('My Profile', 'licence', '#/components/schemas/LicenceInput'),
            '/me/courses' => $this->crudCollection('My Profile', 'course', 'courses', '#/components/schemas/CourseInput', array('title' => 'React Fundamentals', 'provider' => 'Frontend Academy')),
            '/me/courses/{id}' => $this->crudItem('My Profile', 'course', '#/components/schemas/CourseInput'),
            '/me/employment' => $this->crudCollection('My Profile', 'employment', 'employment', '#/components/schemas/EmploymentInput', array('company' => 'Tech Corp', 'position' => 'Senior Engineer', 'start_date' => '2023-01-01')),
            '/me/employment/{id}' => $this->crudItem('My Profile', 'employment', '#/components/schemas/EmploymentInput'),
            '/me/bidding' => array('get' => $this->simpleOp('Bidding', 'Get bidding dashboard data for the authenticated alumni', 'success', array('bidding' => array('monthly_wins' => 1, 'max_wins' => 4, 'remaining_slots' => 3)), array('CookieSession' => array()))),
            '/me/bids' => array(
                'get' => $this->simpleOp('Bidding', 'List bids for the authenticated alumni', 'success', array('bids' => array(array('id' => 7, 'amount' => 250.00, 'bid_date' => '2026-04-06', 'status' => 'pending'))), array('CookieSession' => array())),
                'post' => $this->writeOp('Bidding', 'Create a new bid', '#/components/schemas/BidCreate', array('status' => 'created', 'message' => 'Bid placed successfully. You are currently in the lead!', 'bid' => array('id' => 7, 'amount' => 250.00)), array('CookieSession' => array()))
            ),
            '/me/bids/{id}' => array(
                'get' => $this->simpleOp('Bidding', 'Get a bid', 'success', array('bid' => array('id' => 7, 'amount' => 250.00, 'status' => 'pending')), array('CookieSession' => array()), TRUE),
                'patch' => $this->writeOp('Bidding', 'Increase a bid amount', '#/components/schemas/BidUpdate', array('status' => 'success', 'message' => 'Bid updated. You are now in the lead!', 'bid' => array('id' => 7, 'amount' => 300.00)), array('CookieSession' => array()), TRUE, FALSE)
            ),
            '/me/sponsorships' => array(
                'get' => $this->simpleOp('Sponsorships', 'List sponsorship offers for the authenticated alumni', 'success', array('accepted_total' => 400.00, 'sponsorships' => array(array('id' => 3, 'sponsor_name' => 'CareerCert', 'status' => 'accepted'))), array('CookieSession' => array())),
                'post' => $this->writeOp('Sponsorships', 'Create a sponsorship offer entry', '#/components/schemas/SponsorshipCreate', array('status' => 'created', 'message' => 'Sponsorship offer saved successfully.', 'sponsorship' => array('id' => 3)), array('CookieSession' => array()))
            ),
            '/me/sponsorships/{id}' => array(
                'get' => $this->simpleOp('Sponsorships', 'Get a sponsorship offer', 'success', array('sponsorship' => array('id' => 3, 'status' => 'accepted')), array('CookieSession' => array()), TRUE),
                'patch' => $this->writeOp('Sponsorships', 'Update sponsorship status', '#/components/schemas/SponsorshipStatusUpdate', array('status' => 'success', 'message' => 'Sponsorship status updated.', 'sponsorship' => array('id' => 3, 'status' => 'rejected')), array('CookieSession' => array()), TRUE, FALSE),
                'delete' => $this->deleteOp('Sponsorships', 'Delete a sponsorship offer', array('CookieSession' => array()), TRUE)
            ),
            '/me/events' => array(
                'get' => $this->simpleOp('Events', 'List event participations for the authenticated alumni', 'success', array('max_wins' => 4, 'events' => array(array('id' => 5, 'event_name' => 'Westminster Alumni Meetup'))), array('CookieSession' => array())),
                'post' => $this->writeOp('Events', 'Record an alumni event participation', '#/components/schemas/EventCreate', array('status' => 'created', 'message' => 'Event participation recorded.', 'event' => array('id' => 5, 'event_name' => 'Westminster Alumni Meetup')), array('CookieSession' => array()))
            ),
            '/me/events/{id}' => array(
                'get' => $this->simpleOp('Events', 'Get an event participation record', 'success', array('event' => array('id' => 5, 'event_name' => 'Westminster Alumni Meetup')), array('CookieSession' => array()), TRUE),
                'delete' => $this->deleteOp('Events', 'Delete an event participation record', array('CookieSession' => array()), TRUE)
            )
        );
    }

    private function adminPaths()
    {
        return array(
            '/admin/api-clients' => array(
                'get' => $this->simpleOp('Admin', 'List API clients and assigned scopes', 'success', array('clients' => array(array('id' => 1, 'client_name' => 'Analytics Dashboard', 'scope' => 'read:alumni,read:analytics'))), array('CookieSession' => array())),
                'post' => $this->writeOp('Admin', 'Create a scoped API client', '#/components/schemas/ApiClientCreate', array('status' => 'created', 'message' => 'API client created successfully.', 'client' => array('id' => 4, 'scope' => 'read:alumni_of_day')), array('CookieSession' => array()))
            ),
            '/admin/api-clients/{id}' => array(
                'patch' => $this->writeOp('Admin', 'Activate or revoke an API client', '#/components/schemas/ApiClientStatusUpdate', array('status' => 'success', 'message' => 'API client revoked.', 'client' => array('id' => 1, 'is_active' => false)), array('CookieSession' => array()), TRUE, FALSE)
            ),
            '/admin/api-clients/{id}/logs' => array(
                'get' => $this->simpleOp('Admin', 'List request logs for one API client', 'success', array('logs' => array(array('endpoint' => 'api/v1/analytics/overview', 'method' => 'GET'))), array('CookieSession' => array()), TRUE)
            ),
            '/admin/api-stats' => array(
                'get' => $this->simpleOp('Admin', 'Return API usage statistics by client and endpoint', 'success', array('stats' => array('client_stats' => array(), 'endpoint_stats' => array(), 'recent_access' => array())), array('CookieSession' => array()))
            ),
            '/admin/select-winner' => array(
                'post' => $this->writeOp('Admin', 'Manually run featured-alumni winner selection for a date', '#/components/schemas/WinnerSelectionInput', array('status' => 'success', 'message' => 'Winner selected.'), array('CookieSession' => array()), FALSE, FALSE)
            )
        );
    }

    private function recordSchema($required)
    {
        $properties = array();
        foreach ($required as $field) {
            $properties[$field] = array('type' => 'string');
        }
        $properties['url'] = array('type' => 'string', 'format' => 'uri', 'nullable' => TRUE);
        $properties['completion_date'] = array('type' => 'string', 'format' => 'date', 'nullable' => TRUE);

        return array('type' => 'object', 'required' => $required, 'properties' => $properties);
    }

    private function crudCollection($tag, $item_key, $collection_key, $schema_ref, $example_item)
    {
        return array(
            'get' => $this->simpleOp($tag, 'List ' . $collection_key, 'success', array($collection_key => array()), array('CookieSession' => array())),
            'post' => $this->writeOp($tag, 'Create ' . $item_key, $schema_ref, array('status' => 'created', 'message' => ucfirst($item_key) . ' added successfully.', $item_key => array_merge(array('id' => 1), $example_item)), array('CookieSession' => array()))
        );
    }

    private function crudItem($tag, $item_key, $schema_ref)
    {
        return array(
            'get' => $this->simpleOp($tag, 'Get ' . $item_key, 'success', array($item_key => array('id' => 1)), array('CookieSession' => array()), TRUE),
            'patch' => $this->writeOp($tag, 'Update ' . $item_key, $schema_ref, array('status' => 'success', 'message' => ucfirst($item_key) . ' updated successfully.', $item_key => array('id' => 1)), array('CookieSession' => array()), TRUE, FALSE),
            'delete' => $this->deleteOp($tag, 'Delete ' . $item_key, array('CookieSession' => array()), TRUE)
        );
    }

    private function uploadOp()
    {
        return array(
            'tags' => array('My Profile'),
            'summary' => 'Upload or replace the authenticated alumni profile image',
            'security' => array(array('CookieSession' => array())),
            'requestBody' => array(
                'required' => TRUE,
                'content' => array(
                    'multipart/form-data' => array(
                        'schema' => array(
                            'type' => 'object',
                            'required' => array('profile_image'),
                            'properties' => array('profile_image' => array('type' => 'string', 'format' => 'binary'))
                        )
                    )
                )
            ),
            'responses' => array(
                '200' => $this->jsonResponse('Profile image uploaded', array('status' => 'success', 'message' => 'Profile image uploaded successfully.')),
                '401' => $this->errorResponse('Session login required.'),
                '422' => $this->errorResponse('profile_image file is required.')
            )
        );
    }

    private function simpleOp($tag, $summary, $status, $payload, $security, $with_id = FALSE)
    {
        $op = array(
            'tags' => array($tag),
            'summary' => $summary,
            'security' => array($security),
            'responses' => array(
                '200' => $this->jsonResponse($summary, array_merge(array('status' => $status), $payload)),
                '401' => $this->errorResponse('Unauthorized')
            )
        );

        if ($with_id) {
            $op['parameters'] = array(
                array('name' => 'id', 'in' => 'path', 'required' => TRUE, 'schema' => array('type' => 'integer'))
            );
        }

        return $op;
    }

    private function writeOp($tag, $summary, $schema_ref, $example, $security, $with_id = FALSE, $created = TRUE)
    {
        $status_code = $created ? '201' : '200';
        $op = array(
            'tags' => array($tag),
            'summary' => $summary,
            'security' => array($security),
            'requestBody' => array(
                'required' => TRUE,
                'content' => array(
                    'application/json' => array(
                        'schema' => array('$ref' => $schema_ref)
                    )
                )
            ),
            'responses' => array(
                $status_code => $this->jsonResponse($summary, $example),
                '401' => $this->errorResponse('Unauthorized'),
                '422' => $this->errorResponse('Validation failed')
            )
        );

        if ($with_id) {
            $op['parameters'] = array(
                array('name' => 'id', 'in' => 'path', 'required' => TRUE, 'schema' => array('type' => 'integer'))
            );
        }

        return $op;
    }

    private function deleteOp($tag, $summary, $security, $with_id = FALSE)
    {
        $op = array(
            'tags' => array($tag),
            'summary' => $summary,
            'security' => array($security),
            'responses' => array(
                '204' => array('description' => 'Record deleted'),
                '401' => $this->errorResponse('Unauthorized')
            )
        );

        if ($with_id) {
            $op['parameters'] = array(
                array('name' => 'id', 'in' => 'path', 'required' => TRUE, 'schema' => array('type' => 'integer'))
            );
        }

        return $op;
    }

    private function jsonResponse($description, $example)
    {
        return array(
            'description' => $description,
            'content' => array(
                'application/json' => array(
                    'schema' => array('type' => 'object'),
                    'example' => $example
                )
            )
        );
    }

    private function errorResponse($message)
    {
        return array(
            'description' => $message,
            'content' => array(
                'application/json' => array(
                    'schema' => array('$ref' => '#/components/schemas/Error'),
                    'example' => array(
                        'error' => 'Error',
                        'message' => $message
                    )
                )
            )
        );
    }
}
