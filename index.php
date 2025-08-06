<?php
require_once 'includes/init.php';
require_once 'includes/header.php';
require_once 'includes/navbar.php';
?>

<main>
    <?php
    // Include hero section
    require_once 'sections/hero.php';
    
    // Include about section
    require_once 'sections/about.php';
    
    // Include menu section
    require_once 'sections/menu.php';
    
    // Include testimonials section
    require_once 'sections/testimonials.php';
    
    // Include contact section
    require_once 'sections/contact.php';
    ?>
</main>

<?php
require_once 'includes/footer.php';
?>