<template>

</template>

<script>

    $(document).ready(function() {
        $("#show_hide_password_1 a").on('click', function(event) {
            event.preventDefault();
            if($('#show_hide_password_1 input').attr("type") == "text"){
                $('#show_hide_password_1 input').attr('type', 'password');
                $('#show_hide_password_1 i').addClass( "fa-eye-slash" );
                $('#show_hide_password_1 i').removeClass( "fa-eye" );
            }else if($('#show_hide_password_1 input').attr("type") == "password"){
                $('#show_hide_password_1 input').attr('type', 'text');
                $('#show_hide_password_1 i').removeClass( "fa-eye-slash" );
                $('#show_hide_password_1 i').addClass( "fa-eye" );
            }
        });
        $("#show_hide_password_2 a").on('click', function(event) {
            event.preventDefault();
            if($('#show_hide_password_2 input').attr("type") == "text"){
                $('#show_hide_password_2 input').attr('type', 'password');
                $('#show_hide_password_2 i').addClass( "fa-eye-slash" );
                $('#show_hide_password_2 i').removeClass( "fa-eye" );
            }else if($('#show_hide_password_2 input').attr("type") == "password"){
                $('#show_hide_password_2 input').attr('type', 'text');
                $('#show_hide_password_2 i').removeClass( "fa-eye-slash" );
                $('#show_hide_password_2 i').addClass( "fa-eye" );
            }
        });
    });

</script>