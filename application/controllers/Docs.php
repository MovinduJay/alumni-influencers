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
            'title'    => 'API Documentation',
            'base_url' => base_url()
        );

        $this->load->view('api/docs', $data);
    }

    public function spec()
    {
        $spec = array(
            'openapi' => '3.0.3',
            'info' => array(
                'title'       => 'Alumni Influencers Platform API',
                'description' => 'REST API for featured alumni and alumni resources. Canonical collection URIs use plural nouns and support query-parameter filtering, pagination, and field selection.',
                'version'     => '1.1.0',
                'contact' => array(
                    'name'  => 'Phantasmagoria Ltd',
                    'email' => 'api@phantasmagoria.com'
                ),
                'license' => array(
                    'name' => 'MIT'
                )
            ),
            'servers' => array(
                array(
                    'url'         => base_url('api/v1'),
                    'description' => 'API v1'
                )
            ),
            'components' => array(
                'securitySchemes' => array(
                    'BearerAuth' => array(
                        'type'         => 'http',
                        'scheme'       => 'bearer',
                        'bearerFormat' => 'API Token',
                        'description'  => 'Enter your bearer token obtained from the admin panel. Token permissions are assigned through normalized scope records such as featured:read, alumni:read, and alumni:write.'
                    )
                ),
                'schemas' => array(
                    'Alumni' => array(
                        'type' => 'object',
                        'properties' => array(
                            'id'            => array('type' => 'integer', 'example' => 1),
                            'first_name'    => array('type' => 'string', 'example' => 'John'),
                            'last_name'     => array('type' => 'string', 'example' => 'Smith'),
                            'bio'           => array('type' => 'string', 'example' => 'Software engineer with 10 years experience'),
                            'linkedin_url'  => array('type' => 'string', 'example' => 'https://linkedin.com/in/johnsmith'),
                            'profile_image' => array('type' => 'string', 'nullable' => TRUE, 'example' => 'https://example.com/uploads/profile.jpg'),
                            'degrees'       => array('type' => 'array', 'items' => array('$ref' => '#/components/schemas/Degree')),
                            'certifications'=> array('type' => 'array', 'items' => array('$ref' => '#/components/schemas/Certification')),
                            'licences'      => array('type' => 'array', 'items' => array('$ref' => '#/components/schemas/Licence')),
                            'courses'       => array('type' => 'array', 'items' => array('$ref' => '#/components/schemas/Course')),
                            'employment_history' => array('type' => 'array', 'items' => array('$ref' => '#/components/schemas/Employment'))
                        )
                    ),
                    'FeaturedAlumni' => array(
                        'type' => 'object',
                        'properties' => array(
                            'featured_date' => array('type' => 'string', 'format' => 'date'),
                            'alumni_id' => array('type' => 'integer'),
                            'bid_id' => array('type' => 'integer'),
                            'first_name' => array('type' => 'string'),
                            'last_name' => array('type' => 'string'),
                            'bio' => array('type' => 'string'),
                            'linkedin_url' => array('type' => 'string'),
                            'profile_image' => array('type' => 'string', 'nullable' => TRUE)
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
                    'Degree' => array(
                        'type' => 'object',
                        'properties' => array(
                            'id'              => array('type' => 'integer'),
                            'title'           => array('type' => 'string'),
                            'institution'     => array('type' => 'string'),
                            'url'             => array('type' => 'string'),
                            'completion_date' => array('type' => 'string', 'format' => 'date')
                        )
                    ),
                    'Certification' => array(
                        'type' => 'object',
                        'properties' => array(
                            'id' => array('type' => 'integer'),
                            'title' => array('type' => 'string'),
                            'issuer' => array('type' => 'string'),
                            'url' => array('type' => 'string'),
                            'completion_date' => array('type' => 'string', 'format' => 'date')
                        )
                    ),
                    'Licence' => array(
                        'type' => 'object',
                        'properties' => array(
                            'id' => array('type' => 'integer'),
                            'title' => array('type' => 'string'),
                            'awarding_body' => array('type' => 'string'),
                            'url' => array('type' => 'string'),
                            'completion_date' => array('type' => 'string', 'format' => 'date')
                        )
                    ),
                    'Course' => array(
                        'type' => 'object',
                        'properties' => array(
                            'id' => array('type' => 'integer'),
                            'title' => array('type' => 'string'),
                            'provider' => array('type' => 'string'),
                            'url' => array('type' => 'string'),
                            'completion_date' => array('type' => 'string', 'format' => 'date')
                        )
                    ),
                    'Employment' => array(
                        'type' => 'object',
                        'properties' => array(
                            'id' => array('type' => 'integer'),
                            'company' => array('type' => 'string'),
                            'position' => array('type' => 'string'),
                            'start_date' => array('type' => 'string', 'format' => 'date'),
                            'end_date' => array('type' => 'string', 'format' => 'date', 'nullable' => TRUE)
                        )
                    ),
                    'Error' => array(
                        'type' => 'object',
                        'properties' => array(
                            'error'   => array('type' => 'string'),
                            'message' => array('type' => 'string'),
                            'required_scope' => array('type' => 'string')
                        )
                    )
                )
            ),
            'security' => array(
                array('BearerAuth' => array())
            ),
            'paths' => array(
                '/featured/today' => array(
                    'get' => array(
                        'tags' => array('Legacy'),
                        'summary' => 'Legacy alias for today\'s featured alumnus',
                        'deprecated' => TRUE,
                        'responses' => array(
                            '200' => array('description' => 'Today\'s featured alumni profile'),
                            '401' => array('description' => 'Unauthorized'),
                            '403' => array('description' => 'Forbidden - Token lacks featured:read scope'),
                            '404' => array('description' => 'No featured alumni for today')
                        )
                    )
                ),
                '/featured' => array(
                    'get' => array(
                        'tags' => array('Legacy'),
                        'summary' => 'Legacy alias for featured alumni collection',
                        'deprecated' => TRUE,
                        'responses' => array(
                            '200' => array('description' => 'List of featured alumni'),
                            '401' => array('description' => 'Unauthorized'),
                            '403' => array('description' => 'Forbidden - Token lacks featured:read scope')
                        )
                    )
                ),
                '/featured-alumni' => array(
                    'get' => array(
                        'tags' => array('Featured Alumni'),
                        'summary' => 'List featured alumni resources',
                        'parameters' => array(
                            array('name' => 'featured_date', 'in' => 'query', 'schema' => array('type' => 'string'), 'description' => 'Use YYYY-MM-DD or current'),
                            array('name' => 'limit', 'in' => 'query', 'schema' => array('type' => 'integer', 'default' => 25)),
                            array('name' => 'offset', 'in' => 'query', 'schema' => array('type' => 'integer', 'default' => 0)),
                            array('name' => 'sort', 'in' => 'query', 'schema' => array('type' => 'string', 'example' => '-featured_date'))
                        ),
                        'responses' => array(
                            '200' => array('description' => 'Featured alumni collection'),
                            '401' => array('description' => 'Unauthorized'),
                            '403' => array('description' => 'Forbidden - Token lacks featured:read scope')
                        )
                    )
                ),
                '/featured-alumni/{date}' => array(
                    'get' => array(
                        'tags' => array('Featured Alumni'),
                        'summary' => 'Get a featured alumni resource by date',
                        'parameters' => array(
                            array('name' => 'date', 'in' => 'path', 'required' => TRUE, 'schema' => array('type' => 'string'), 'description' => 'YYYY-MM-DD or current')
                        ),
                        'responses' => array(
                            '200' => array('description' => 'Featured alumni resource'),
                            '401' => array('description' => 'Unauthorized'),
                            '403' => array('description' => 'Forbidden - Token lacks featured:read scope'),
                            '404' => array('description' => 'Featured alumni not found')
                        )
                    )
                ),
                '/alumni/{id}' => array(
                    'get' => array(
                        'tags' => array('Alumni'),
                        'summary' => 'Get alumni profile',
                        'parameters' => array(
                            array('name' => 'id', 'in' => 'path', 'required' => TRUE, 'schema' => array('type' => 'integer')),
                            array('name' => 'fields', 'in' => 'query', 'schema' => array('type' => 'string'), 'description' => 'Comma-separated field selection')
                        ),
                        'responses' => array(
                            '200' => array('description' => 'Alumni profile data'),
                            '401' => array('description' => 'Unauthorized'),
                            '403' => array('description' => 'Forbidden - Token lacks alumni:read scope'),
                            '404' => array('description' => 'Alumni not found')
                        )
                    ),
                    'patch' => array(
                        'tags' => array('Alumni'),
                        'summary' => 'Partially update an alumni resource',
                        'requestBody' => array(
                            'required' => TRUE,
                            'content' => array(
                                'application/json' => array(
                                    'schema' => array('$ref' => '#/components/schemas/AlumniPatch')
                                )
                            )
                        ),
                        'responses' => array(
                            '200' => array('description' => 'Updated alumni resource'),
                            '401' => array('description' => 'Unauthorized'),
                            '403' => array('description' => 'Forbidden - Token lacks alumni:write scope'),
                            '404' => array('description' => 'Alumni not found')
                        )
                    ),
                    'delete' => array(
                        'tags' => array('Alumni'),
                        'summary' => 'Soft-delete an alumni resource',
                        'responses' => array(
                            '204' => array('description' => 'Resource deactivated'),
                            '401' => array('description' => 'Unauthorized'),
                            '403' => array('description' => 'Forbidden - Token lacks alumni:write scope'),
                            '404' => array('description' => 'Alumni not found')
                        )
                    )
                ),
                '/alumni' => array(
                    'get' => array(
                        'tags' => array('Alumni'),
                        'summary' => 'List alumni resources',
                        'parameters' => array(
                            array('name' => 'name', 'in' => 'query', 'schema' => array('type' => 'string')),
                            array('name' => 'limit', 'in' => 'query', 'schema' => array('type' => 'integer', 'default' => 25)),
                            array('name' => 'offset', 'in' => 'query', 'schema' => array('type' => 'integer', 'default' => 0)),
                            array('name' => 'sort', 'in' => 'query', 'schema' => array('type' => 'string', 'example' => '-created_at')),
                            array('name' => 'fields', 'in' => 'query', 'schema' => array('type' => 'string'), 'description' => 'Comma-separated field selection')
                        ),
                        'responses' => array(
                            '200' => array('description' => 'List of alumni'),
                            '401' => array('description' => 'Unauthorized'),
                            '403' => array('description' => 'Forbidden - Token lacks alumni:read scope')
                        )
                    ),
                    'post' => array(
                        'tags' => array('Alumni'),
                        'summary' => 'Create an alumni resource',
                        'requestBody' => array(
                            'required' => TRUE,
                            'content' => array(
                                'application/json' => array(
                                    'schema' => array('$ref' => '#/components/schemas/AlumniCreate')
                                )
                            )
                        ),
                        'responses' => array(
                            '201' => array('description' => 'Created alumni resource'),
                            '401' => array('description' => 'Unauthorized'),
                            '403' => array('description' => 'Forbidden - Token lacks alumni:write scope'),
                            '409' => array('description' => 'Conflict - duplicate email'),
                            '422' => array('description' => 'Validation failed')
                        )
                    )
                )
            )
        );

        $this->output
            ->set_content_type('application/json')
            ->set_output(json_encode($spec, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
    }
}
