<div class="wrap">
    <h2>Taxonomy Taxi</h2>
    <form class="taxonomy-taxi" method="POST" action="options.php">
        <?php
        settings_fields('taxonomy_taxi');
        do_settings_sections('taxonomy_taxi');
        submit_button();
        ?>
    </form>
</div>