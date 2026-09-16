<?php

declare(strict_types=1);

namespace Alnaseeg\BranchManager\Branch;

final class BranchSelector
{
    public function __construct(
        private readonly BranchRepository $branches,
        private readonly BranchSession $session,
        private readonly BranchSelectorRenderer $renderer
    ) {
    }

    /**
     * Register WordPress hooks.
     */
    public function register(): void
    {
        add_shortcode('branch_selector', [$this, 'render']);

        add_action('init', [$this, 'handleSubmission']);
    }

    /**
     * Render the branch selector.
     */
    public function render(): string
    {
        return $this->renderer->render(
            $this->branches->all(),
            $this->session->get()
        );
    }

    /**
     * Handle selector submission.
     */
    public function handleSubmission(): void
    {
        if (! isset($_POST['abm_select_branch'])) {
            return;
        }

        if (! isset($_POST['abm_nonce'])) {
            return;
        }

        if (! wp_verify_nonce(
            sanitize_text_field(wp_unslash($_POST['abm_nonce'])),
            'abm_select_branch'
        )) {
            return;
        }

        $branchId = isset($_POST['branch_id'])
            ? (int) $_POST['branch_id']
            : 0;

        if ($branchId <= 0) {
            return;
        }

        $branch = $this->branches->findById($branchId);

        if ($branch === null) {
            return;
        }

        $this->session->set($branchId);

        wp_safe_redirect(remove_query_arg([]));

        exit;
    }
}