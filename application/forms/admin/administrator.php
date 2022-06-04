<?php
    declare(strict_types = 1);
?>
<form class="form-horizontal" action="<?php echo $pageURL; ?>" method="post">
    <?php
        if(count($errors) > 0)
        {
            echo '<ul class="alert alert-danger list-unstyled">';
            
            if(in_array('FirstNameBlank', $errors))
            {
                echo '<li class="member-account__error">First Name cannot be blank.</li>';
            }

            if(in_array('LastNameBlank', $errors))
            {
                echo '<li class="member-account__error">Last Name cannot be blank.</li>';
            }

            if(in_array('EmailBlank', $errors))
            {
                echo '<li class="member-account__error">Email cannot be blank.</li>';
            }

            if(in_array('EmailInvalid', $errors))
            {
                echo '<li class="member-account__error">Please provide a valid Email.</li>';
            }

            if(in_array('EmailInUse', $errors))
            {
                echo '<li class="member-account__error">Email is already in use by another account.</li>';
            }

            echo '</ul>';
        }
    ?>
    <fieldset class="form-group">First Name <span class="required">*</span>
        <input type="text" class="form-control<?php echo (in_array('FirstName', $errors) ? ' is-invalid' : ''); ?>" name="txtFirstName" value="<?php echo htmlspecialchars($firstName); ?>" />
    </fieldset>

    <fieldset class="form-group">Last Name <span class="required">*</span>
        <input type="text" class="form-control<?php echo (in_array('LastName', $errors) ? ' is-invalid' : ''); ?>" name="txtLastName" value="<?php echo htmlspecialchars($lastName); ?>" />
    </fieldset>

    <fieldset class="form-group">Email <span class="required">*</span>
        <input type="text" class="form-control<?php echo (in_array('Email', $errors) ? ' is-invalid' : ''); ?>" name="txtEmail" value="<?php echo htmlspecialchars($email); ?>" />
    </fieldset>

    <?php
        if(isset($activeAdministrator) && $administrator->getAdministratorID() !== $activeAdministrator->getAdministratorID())
        {
        ?>
            <fieldset class="posts__section">
                <input id="enabled-check" type="checkbox" class="form-check-input" name="chkEnabled" value="Y"<?php echo ($enabled ? ' checked' : ''); ?> />
                <label class="form-check-label" for="enabled-check">
                    Enabled
                </label>
            </fieldset>
        <?php
        }
    ?>

    <input type="hidden" name="txtFormType" value="<?php echo (isset($activeAdministrator) ? 'SAVEADMIN' : 'ADDADMIN'); ?>" />
    <input type="submit" class="btn btn-primary" name="" value="Save" />&nbsp;&nbsp;&nbsp;or&nbsp;&nbsp;&nbsp;<a href="<?php echo $siteURL; ?>administrators/<?php echo (isset($activeAdministrator) ? 'view.html?administrator=' . htmlspecialchars($activeAdministrator->getAdministratorCode()) : ''); ?>">cancel</a>
</form>