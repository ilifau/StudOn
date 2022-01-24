
    if ($('#username').val()) {
        showLoginForm(true);
    }
    else {
        showLoginForm({LOGIN_INIT_DISPLAY});
    }

    function showLoginForm(display)
    {
        if (typeof display === 'undefined') {
            display = ! $('.ilc_section_LoginFormHeadline h2').hasClass('open');
        }

        if (display)
        {
            $('.ilc_section_LoginFormHidden').show();
            $('.ilc_section_LoginFormHeadline h2').addClass('open');

            if ($('#username').val()) {
                $('#password').focus();
            }
            else {
                $('#username').focus();
            }

            showLoginInfo(false);
        }
        else {
            $('.ilc_section_LoginFormHidden').hide();
            $('.ilc_section_LoginFormHeadline h2').removeClass('open');
        }
        return false;
    }

    function showLoginInfo(display)
    {
        if (typeof display === 'undefined') {
            display = ! $('.ilc_section_LoginInfoHeadline h2').hasClass('open');
        }

        if (display)
        {
            $('.ilc_section_LoginInfoHidden').show();
            $('.ilc_section_LoginInfoHeadline h2').addClass('open');

            showLoginForm(false);
        }
        else {
            $('.ilc_section_LoginInfoHidden').hide();
            $('.ilc_section_LoginInfoHeadline h2').removeClass('open');
        }
        return false;
    }

    $('.ilc_section_LoginFormHeadline h2 a').on('click', function() {showLoginForm();});
    $('.ilc_section_LoginInfoHeadline h2 a').on('click', function() {showLoginInfo();});
