<?php
declare(strict_types=1);

require_once __DIR__ . '/BaseController.php';
require_once __DIR__ . '/../models/EligibleProgram.php';

class EligibleProgramsController extends BaseController
{
    public function index(): void
    {
        $this->requireLogin();
        $this->setTitle('Eligible Programs');

        $model = new EligibleProgram();
        $rows  = $model->getPublishedBrochures(true);

        $this->view('eligible_programs/index', [
            'rows'        => $rows,
            'menu_slug'   => ELIGIBLE_PROGRAMS_SLUG,
            'menu_title'  => ELIGIBLE_PROGRAMS_MENU_TITLE,
            'csrf_token'  => $this->generateCSRFToken(),
            'public_base' => rtrim(PARROT_MIS_PUBLIC_URL, '/'),
            'success'     => $this->getFlashMessage('success'),
            'error'       => $this->getFlashMessage('error'),
        ]);
    }

    public function apiList(): void
    {
        $model = new EligibleProgram();
        $rows  = $model->getPublishedBrochures(false);

        $this->json([
            'success' => true,
            'count'   => count($rows),
            'menu'    => [
                'title' => ELIGIBLE_PROGRAMS_MENU_TITLE,
                'slug'  => ELIGIBLE_PROGRAMS_SLUG,
            ],
            'data'    => $rows,
        ]);
    }

    public function toggleHidden(): void
    {
        $this->requireLogin();
        if (!$this->validateCSRF()) {
            $this->json(['success' => false, 'message' => 'Invalid token'], 400);
        }

        $slug   = (string) $this->getInput('slug', '');
        $hidden = (string) $this->getInput('hidden', '0') === '1';
        if ($slug === '') {
            $this->json(['success' => false, 'message' => 'Missing slug'], 400);
        }

        (new EligibleProgram())->setHidden($slug, $hidden);
        $this->json(['success' => true, 'is_hidden' => $hidden]);
    }

    public function toggleFeatured(): void
    {
        $this->requireLogin();
        if (!$this->validateCSRF()) {
            $this->json(['success' => false, 'message' => 'Invalid token'], 400);
        }

        $slug     = (string) $this->getInput('slug', '');
        $featured = (string) $this->getInput('featured', '0') === '1';
        if ($slug === '') {
            $this->json(['success' => false, 'message' => 'Missing slug'], 400);
        }

        (new EligibleProgram())->setFeatured($slug, $featured);
        $this->json(['success' => true, 'is_featured' => $featured]);
    }

    public function updateLabel(): void
    {
        $this->requireLogin();
        if (!$this->validateCSRF()) {
            $this->json(['success' => false, 'message' => 'Invalid token'], 400);
        }

        $slug     = (string) $this->getInput('slug', '');
        $title    = trim((string) $this->getInput('display_title', ''));
        $subtitle = trim((string) $this->getInput('display_subtitle', ''));

        if ($slug === '') {
            $this->json(['success' => false, 'message' => 'Missing slug'], 400);
        }

        (new EligibleProgram())->setDisplayLabels(
            $slug,
            $title === '' ? null : $title,
            $subtitle === '' ? null : $subtitle
        );

        $this->json(['success' => true]);
    }
}
