<?php
$page_title = "Accueil";
require_once 'includes/header.php';
?>

<div class="jumbotron bg-light p-5 rounded mb-4">
    <h1 class="display-4">Bienvenue sur <?php echo htmlspecialchars(APP_NAME); ?> !</h1>
    <p class="lead">La solution simple pour organiser vos commandes groupées.</p>
    <hr class="my-4">
    <p>
        Créez facilement des commandes groupées, invitez vos amis, et optimisez les frais de livraison.
        <?php if (!isLoggedIn()): ?>
            Inscrivez-vous ou connectez-vous pour commencer.
        <?php endif; ?>
    </p>
    <?php if (!isLoggedIn()): ?>
    <div class="mt-4">
        <a href="/register.php" class="btn btn-primary btn-lg me-2">S'inscrire</a>
        <a href="/login.php" class="btn btn-secondary btn-lg">Se connecter</a>
    </div>
    <?php else: ?>
    <div class="mt-4">
        <a href="/create_commande.php" class="btn btn-primary btn-lg me-2">Créer une commande</a>
        <a href="/dashboard.php" class="btn btn-secondary btn-lg">Mon tableau de bord</a>
    </div>
    <?php endif; ?>
</div>

<div class="row mb-4">
    <div class="col-md-4">
        <div class="card h-100">
            <div class="card-body">
                <h5 class="card-title">Créez des commandes</h5>
                <p class="card-text">Définissez les produits disponibles, les dates limites et les conditions de récupération.</p>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card h-100">
            <div class="card-body">
                <h5 class="card-title">Partagez avec vos amis</h5>
                <p class="card-text">Invitez facilement des participants grâce à un lien unique pour chaque commande.</p>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card h-100">
            <div class="card-body">
                <h5 class="card-title">Économisez sur les frais</h5>
                <p class="card-text">Réduisez les frais de port grâce aux commandes groupées et répartissez-les équitablement.</p>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-12">
        <h2 class="mb-4">Comment ça marche ?</h2>
    </div>
</div>

<div class="row mb-5">
    <div class="col-md-3 text-center mb-3">
        <div class="bg-primary text-white rounded-circle mx-auto mb-3" style="width: 60px; height: 60px; line-height: 60px; font-size: 24px;">1</div>
        <h5>Créez une commande</h5>
        <p>Définissez les produits, les dates et les frais de port.</p>
    </div>
    <div class="col-md-3 text-center mb-3">
        <div class="bg-primary text-white rounded-circle mx-auto mb-3" style="width: 60px; height: 60px; line-height: 60px; font-size: 24px;">2</div>
        <h5>Invitez des participants</h5>
        <p>Partagez le lien unique de votre commande.</p>
    </div>
    <div class="col-md-3 text-center mb-3">
        <div class="bg-primary text-white rounded-circle mx-auto mb-3" style="width: 60px; height: 60px; line-height: 60px; font-size: 24px;">3</div>
        <h5>Suivez les commandes</h5>
        <p>Gérez les participants et leurs paiements.</p>
    </div>
    <div class="col-md-3 text-center mb-3">
        <div class="bg-primary text-white rounded-circle mx-auto mb-3" style="width: 60px; height: 60px; line-height: 60px; font-size: 24px;">4</div>
        <h5>Organisez la distribution</h5>
        <p>Récupérez les commandes et distribuez-les.</p>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>