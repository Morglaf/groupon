<?php
require_once 'includes/config.php';
require_once 'includes/lang.php';
require_once 'includes/auth.php';

// Rediriger les utilisateurs non connectés vers la page de connexion
requireLogin('login.php');

require_once 'includes/header.php';
$page_title = __('help');
?>

<div class="jumbotron bg-light p-5 rounded mb-4">
    <h1 class="display-4"><?php echo __('help'); ?> - <?php echo htmlspecialchars(APP_NAME); ?></h1>
    <p class="lead"><?php echo __('help_description'); ?></p>
    <hr class="my-4">
    <p><?php echo __('help_welcome_message'); ?></p>
</div>

<div class="row mb-4">
    <div class="col-md-4">
        <div class="card h-100">
            <div class="card-body">
                <h5 class="card-title"><?php echo __('create_orders'); ?></h5>
                <p class="card-text"><?php echo __('create_orders_text'); ?></p>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card h-100">
            <div class="card-body">
                <h5 class="card-title"><?php echo __('share_with_friends'); ?></h5>
                <p class="card-text"><?php echo __('share_with_friends_text'); ?></p>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card h-100">
            <div class="card-body">
                <h5 class="card-title"><?php echo __('save_on_fees'); ?></h5>
                <p class="card-text"><?php echo __('save_on_fees_text'); ?></p>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-12">
        <h2 class="mb-4"><?php echo __('how_it_works'); ?></h2>
    </div>
</div>

<div class="row mb-5">
    <div class="col-md-3 text-center mb-3">
        <div class="bg-primary text-white rounded-circle mx-auto mb-3" style="width: 60px; height: 60px; line-height: 60px; font-size: 24px;">1</div>
        <h5><?php echo __('step1'); ?></h5>
        <p><?php echo __('step1_text'); ?></p>
    </div>
    <div class="col-md-3 text-center mb-3">
        <div class="bg-primary text-white rounded-circle mx-auto mb-3" style="width: 60px; height: 60px; line-height: 60px; font-size: 24px;">2</div>
        <h5><?php echo __('step2'); ?></h5>
        <p><?php echo __('step2_text'); ?></p>
    </div>
    <div class="col-md-3 text-center mb-3">
        <div class="bg-primary text-white rounded-circle mx-auto mb-3" style="width: 60px; height: 60px; line-height: 60px; font-size: 24px;">3</div>
        <h5><?php echo __('step3'); ?></h5>
        <p><?php echo __('step3_text'); ?></p>
    </div>
    <div class="col-md-3 text-center mb-3">
        <div class="bg-primary text-white rounded-circle mx-auto mb-3" style="width: 60px; height: 60px; line-height: 60px; font-size: 24px;">4</div>
        <h5><?php echo __('step4'); ?></h5>
        <p><?php echo __('step4_text'); ?></p>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
