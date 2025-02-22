// Run Preloader Logic Exclusively in index.php
$(document).ready(function () {
    setTimeout(function () {
        $('#preloader').fadeOut(500, function () {
            $('#content').fadeIn(500); // Show content after preloader fade-out
        });
    }, 1500); // Preloader duration: 1.5 seconds
});

function dispContent(page) {
    page += ".php"; // Append .php extension

    $.ajax({
        url: './views/' + page,
        type: "POST",
        data: {},
        beforeSend: function () {
            // Only show preloader during initial page load, not AJAX content load
            if ($('#content').is(':empty')) {
                $('#preloader').show();
            }
        },
        success: function (response) {
            setTimeout(function () {
                if (response.trim()) {
                    $('#content').html(response).fadeIn(500);
                } else {
                    $('#content').html('<p style="color: red;">No content available.</p>');
                }
            }, 300);
        },
        error: function (e) {
            console.error("AJAX Error:", e.statusText);
            $('#content').html('<p style="color: red;">Error loading content.</p>');
        }
    });
}
