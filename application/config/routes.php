<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/*
| -------------------------------------------------------------------------
| URI ROUTING
| -------------------------------------------------------------------------
| This file lets you re-map URI requests to specific controller functions.
|
| Typically there is a one-to-one relationship between a URL string
| and its corresponding controller class/method. The segments in a
| URL normally follow this pattern:
|
|	example.com/class/method/id/
|
| In some instances, however, you may want to remap this relationship
| so that a different class/function is called than the one
| corresponding to the URL.
|
| Please see the user guide for complete details:
|
|	https://codeigniter.com/userguide3/general/routing.html
|
| -------------------------------------------------------------------------
| RESERVED ROUTES
| -------------------------------------------------------------------------
|
| There are three reserved routes:
|
|	$route['default_controller'] = 'welcome';
|
| This route indicates which controller class should be loaded if the
| URI contains no data. In the above example, the "welcome" class
| would be loaded.
|
|	$route['404_override'] = 'errors/page_missing';
|
| This route will tell the Router which controller/method to use if those
| provided in the URL cannot be matched to a valid route.
|
|	$route['translate_uri_dashes'] = FALSE;
|
| This is not exactly a route, but allows you to automatically route
| controller and method names that contain dashes. '-' isn't a valid
| class or method name character, so it requires translation.
| When you set this option to TRUE, it will replace ALL dashes with
| underscores in the controller and method URI segments.
|
| Examples:	my-controller/index	-> my_controller/index
|		my-controller/my-method	-> my_controller/my_method
*/
$route['default_controller'] = 'welcome';
$route['404_override'] = '';
$route['translate_uri_dashes'] = FALSE;

/*
| -------------------------------------------------------------------------
| Authentication Routes
| -------------------------------------------------------------------------
*/
$route['auth/register'] = 'auth/register';
$route['auth/login'] = 'auth/login';
$route['auth/logout'] = 'auth/logout';
$route['auth/verify/(:any)'] = 'auth/verify/$1';
$route['auth/forgot-password'] = 'auth/forgot_password';
$route['auth/reset-password/(:any)'] = 'auth/reset_password/$1';

/*
| -------------------------------------------------------------------------
| Profile Routes
| -------------------------------------------------------------------------
*/
$route['profile'] = 'profile/index';
$route['profile/edit'] = 'profile/edit';
$route['profile/view/(:num)'] = 'profile/view/$1';
$route['profile/image-upload'] = 'profile/image_upload';

// Profile sub-sections
$route['profile/degrees'] = 'profile/degrees';
$route['profile/degrees/add'] = 'profile/add_degree';
$route['profile/degrees/edit/(:num)'] = 'profile/edit_degree/$1';
$route['profile/degrees/delete/(:num)'] = 'profile/delete_degree/$1';

$route['profile/certifications'] = 'profile/certifications';
$route['profile/certifications/add'] = 'profile/add_certification';
$route['profile/certifications/edit/(:num)'] = 'profile/edit_certification/$1';
$route['profile/certifications/delete/(:num)'] = 'profile/delete_certification/$1';

$route['profile/licences'] = 'profile/licences';
$route['profile/licences/add'] = 'profile/add_licence';
$route['profile/licences/edit/(:num)'] = 'profile/edit_licence/$1';
$route['profile/licences/delete/(:num)'] = 'profile/delete_licence/$1';

$route['profile/courses'] = 'profile/courses';
$route['profile/courses/add'] = 'profile/add_course';
$route['profile/courses/edit/(:num)'] = 'profile/edit_course/$1';
$route['profile/courses/delete/(:num)'] = 'profile/delete_course/$1';

$route['profile/employment'] = 'profile/employment';
$route['profile/employment/add'] = 'profile/add_employment';
$route['profile/employment/edit/(:num)'] = 'profile/edit_employment/$1';
$route['profile/employment/delete/(:num)'] = 'profile/delete_employment/$1';

/*
| -------------------------------------------------------------------------
| Bidding Routes
| -------------------------------------------------------------------------
*/
$route['bidding'] = 'bidding/index';
$route['bidding/place'] = 'bidding/place';
$route['bidding/update/(:num)'] = 'bidding/update_bid/$1';
$route['bidding/history'] = 'bidding/history';
$route['bidding/sponsorships'] = 'bidding/sponsorships';
$route['bidding/sponsorships/add'] = 'bidding/add_sponsorship';
$route['bidding/sponsorships/update/(:num)'] = 'bidding/update_sponsorship/$1';
$route['bidding/sponsorships/delete/(:num)'] = 'bidding/delete_sponsorship/$1';
$route['bidding/events'] = 'bidding/events';
$route['bidding/events/add'] = 'bidding/add_event';
$route['bidding/events/delete/(:num)'] = 'bidding/delete_event/$1';
$route['bidding/select-winner'] = 'bidding/select_winner';

/*
| -------------------------------------------------------------------------
| Admin Routes (API Client Management)
| -------------------------------------------------------------------------
*/
$route['admin/api-clients'] = 'admin/api_clients';
$route['admin/api-clients/create'] = 'admin/create_client';
$route['admin/api-clients/revoke/(:num)'] = 'admin/revoke_client/$1';
$route['admin/api-clients/logs/(:num)'] = 'admin/client_logs/$1';
$route['admin/api-clients/stats'] = 'admin/api_stats';

/*
| -------------------------------------------------------------------------
| Public API Routes (Bearer Token Protected)
| -------------------------------------------------------------------------
*/
$route['api/v1/featured'] = 'api/featured';
$route['api/v1/featured/today'] = 'api/featured_today';
$route['api/v1/featured-alumni/current'] = 'api/featured_alumni_item/current';
$route['api/v1/featured-alumni/(:any)'] = 'api/featured_alumni_item/$1';
$route['api/v1/featured-alumni'] = 'api/featured_alumni_index';
$route['api/v1/alumni/(:num)'] = 'api/alumni/$1';
$route['api/v1/alumni'] = 'api/alumni_list';

/*
| -------------------------------------------------------------------------
| CLI Routes (Cron)
| -------------------------------------------------------------------------
*/
$route['cron/select-winner'] = 'cron/select_winner';

/*
| -------------------------------------------------------------------------
| API Documentation
| -------------------------------------------------------------------------
*/
$route['api-docs'] = 'docs/index';
$route['docs/spec'] = 'docs/spec';
