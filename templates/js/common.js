function objToForm(data, formData) {
    for(let pos in data) {
        formData.append(pos, data[pos]);
    }
    return formData;
}

function showErrors(response, multiple = true) {

    removeErrors();

    if (response["errors"])
    {
        $('#errors').addClass("alert alert-danger");

        if (multiple)
        {
            response["errors"].forEach((element) => {
                $('#errors').append(`<p class="errors__item">${element['title']}</p>`);
            });
        } else
        {
            $('#errors').append(`<p class="errors__item">${response["errors"]}</p>`);
        }

        scrollToElementByID('errors');
        return true;
    }

    return false;
}

function removeErrors() {
    $('#errors').removeClass("alert alert-danger").text('');
    $('.errors__item').remove();
}

function scrollToElementByID(id) {
    let speedScroll = 1100;
    let idScroll = document.getElementById(id);
    let scrollDestination = idScroll.offsetTop;
    $('html').animate({scrollTop: scrollDestination}, speedScroll);
}

function showPreloader() {
    $('#preloader').css('display', 'inherit');
    $('.tab-content').css('display', 'none');
}

function hidePreloader() {
    $('#preloader').css('display', 'none');
    $('.tab-content').css('display', 'block');
}

// function removeSuccess() {
//     $('#success').removeClass("alert alert-danger").text('');
//     $('.success').remove();
// }
//
// function showSuccess(response) {
//     removeErrors();
//     removeSuccess();
//
//     if (response["success"])
//     {
//         $('#success').addClass("alert alert-danger");
//         $('#success').append(`<p class="success">${response["success"]}</p>`);
//
//         scrollToElementByID('success');
//         return true;
//     }
//
//     return false;
// }