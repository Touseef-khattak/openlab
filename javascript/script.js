$(document).ready(function () {

    var link = document.querySelector("link[rel~='icon']");
    if (!link) {
        link = document.createElement('link');
        link.rel = 'icon';
        document.getElementsByTagName('head')[0].appendChild(link);
    }
    link.href = '../images/minilab_logo.png';

    //---------- CNIC Validation ------------
    $('#cnic, #add_user_cnic , #edit_user_cnic').keydown(function () {

        //allow  backspace, tab, ctrl+A, escape, carriage return
        if (event.keyCode == 8 || event.keyCode == 9 || event.keyCode == 37 || event.keyCode == 39 || event.keyCode == 46 ||
            event.keyCode == 27 || event.keyCode == 13 ||
            (event.keyCode == 65 && event.ctrlKey === true))
            return;
        if ((event.keyCode < 48 || event.keyCode > 57))
            event.preventDefault();

        var length = $(this).val().length;

        if (length == 5 || length == 13)
            $(this).val($(this).val() + '-');

    });


    //------------ Add User Modal Box ------------

    $(".R_modal_trigger").click(function (e) {
        e.preventDefault();
        $("#R_modal_name").css({
            "display": "block"
        });

        //  ------- Prevent double scrollbar ---------
        $("body").css({
            "overflow-y": "hidden"
        });
    });
    $(".R_close_modal").click(function () {
        $(".R_modal").css({
            "display": "none"
        });
        //  ------- Prevent double scrollbar ---------
        $("body").css({
            "overflow-y": "auto"
        });
    });

    //------------ Edit User Modal Box ------------

    $(".E_modal_trigger").click(function (e) {
        e.preventDefault();

        $("#E_modal_name").css({
            "display": "block"
        });

        $("body").css({
            "overflow-y": "hidden"
        });

    });


    $(".E_close_modal").click(function () {
        $(".E_modal").css({
            "display": "none"
        });
        //  ------- Prevent double scrollbar ---------
        $("body").css({
            "overflow-y": "auto"
        });

    });
    //------------ Add Doctor Modal Box ------------

    $(".AD_modal_trigger").click(function (e) {
        e.preventDefault();
        $("#AD_modal_name").css({
            "display": "block"
        });

        //  ------- Prevent double scrollbar ---------
        $("body").css({
            "overflow-y": "hidden"
        });
    });
    $(".AD_close_modal").click(function () {
        $(".AD_modal").css({
            "display": "none"
        });
        //  ------- Prevent double scrollbar ---------
        $("body").css({
            "overflow-y": "auto"
        });
    });

    //------------ Edit Doctor Modal Box ------------

    $(".ED_modal_trigger").click(function (e) {
        e.preventDefault();
        $("#ED_modal_name").css({
            "display": "block"
        });

        //  ------- Prevent double scrollbar ---------
        $("body").css({
            "overflow-y": "hidden"
        });


        let vd = e.currentTarget.id;
        var uname;
        var dep;
        var umobile;
        var urole;

        $.ajax({
            type: "POST",
            dataType: "json",
            url: "../php/user_model_ajax.php",
            data: {
                d_id: vd
            },
            cache: false,
            success: function (data) {
                uname = data.name;
                $("#edit_doctor_name").val(uname);
                dep = data.dep;
                $("#edit_doctor_dep").val(dep);
                $("#edit_doctor_mobile").val(data.mobile);
                $("#edit_doctor_hospital").val(data.hospital);
                //                console.log(data);

            },
            error: function (xhr, status, error) {
                console.error(xhr);
            }
        });


    });

    $(".ED_close_modal").click(function () {
        $(".ED_modal").css({
            "display": "none"
        });
        //  ------- Prevent double scrollbar ---------
        $("body").css({
            "overflow-y": "auto"
        });
    });

    //------------ Add Test Modal Box ------------

    $(".AT_modal_trigger").click(function (e) {
        e.preventDefault();
        $("#AT_modal_name").css({
            "display": "block"
        });

        //  ------- Prevent double scrollbar ---------
        $("body").css({
            "overflow-y": "hidden"
        });
    });
    $(".AT_close_modal").click(function () {
        $(".AT_modal").css({
            "display": "none"
        });
        //  ------- Prevent double scrollbar ---------
        $("body").css({
            "overflow-y": "auto"
        });
    });
    //------------ Add Category Modal Box ------------

    $(".AC_modal_trigger").click(function (e) {
        e.preventDefault();
        $("#AC_modal_name").css({
            "display": "block"
        });

        //  ------- Prevent double scrollbar ---------
        $("body").css({
            "overflow-y": "hidden"
        });
    });
    $(".AC_close_modal").click(function () {
        $(".AC_modal").css({
            "display": "none"
        });
        //  ------- Prevent double scrollbar ---------
        $("body").css({
            "overflow-y": "auto"
        });
    });
    //------------ Edit Test Modal Box ------------
    $(".ET_modal_trigger").click(function (e) {
        e.preventDefault();
        $("#ET_modal_name").css({
            "display": "block"
        });

        //  ------- Prevent double scrollbar ---------
        $("body").css({
            "overflow-y": "hidden"
        });
    });
    $(".ET_close_modal").click(function () {
        $(".ET_modal").css({
            "display": "none"
        });
        //  ------- Prevent double scrollbar ---------
        $("body").css({
            "overflow-y": "auto"
        });
    });






});
