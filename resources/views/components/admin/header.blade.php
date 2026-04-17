<style>
    .deliveryCards {
        margin-top: 20px;
        width: 100%;
        display: grid !important;
        grid-template-columns: repeat(4, 1fr);
        grid-gap: 20px;

    }

    .deliveryCards .card-header {
        width: 100%;
        grid-column: 1 / -1;

    }

    .deliveryCards_card {
        width: 100%;
    }

    .deliveryCards__addCard {
        margin-top: 20px;
        order: 10
    }

    .deliveryCards_delete {
        color: #ff407b !important;
    }

    .deliveryCards_delete:hover {
        color: #fff !important
    }

    .faq {
        display: flex;
        flex-direction: column;
        width: 100%;
        align-items: flex-start;
        justify-content: flex-start;

    }

    .add-faq-button {
        order: 10;
        margin-top: 20px;
    }

    .faq-cards-container {
        width: 100%;
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        grid-gap: 20px
    }

    .faq-card:not(:last-child) {
        margin-bottom: 20px;
    }

    .videoReviewCards_card {
        margin-bottom: 20px;
        width: 100%;
    }

    .videoReviewCards__addCard {
        order: 10;
    }

    .videoReviewCards {
        display: flex;
        flex-direction: column;
        align-items: flex-start;
        justify-content: flex-start;
        width: 100%;
    }

    .video-review-card {
        width: 100%;
        margin-bottom: 20px;
    }

    .dropzone {
        width: 100%;
        padding: 20px;
        border: 2px dashed #ccc;
        text-align: center;
        cursor: pointer;
        margin-bottom: 20px;
    }

    .dropzone.dragover {
        border-color: #333;
    }

    #categoryEditForm .form-group img {
        margin-top: 20px;
        display: block;
        max-width: 100px;
    }

    .videoReviewCards_card img,
    .videoReviewCards_card video {
        margin-top: 20px;
    }

    .work-examples-gallery {
        display: flex;
        align-items: flex-start;
        justify-content: flex-start;
        flex-direction: column;
        width: 100%;
    }

    #workExamplesContainer {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        grid-gap: 20px;
        margin-top: 20px;

    }

    .work-example-card {
        display: grid;
        grid-template-columns: 1fr;
        grid-gap: 10px
    }

    .work-examples {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        grid-gap: 10px
    }

    .videoReviewCards {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        grid-gap: 20px
    }

    .videoReviewCards__addCard {
        grid-column: 1 / -1;
        /* Занимает все колонки */
        max-width: fit-content;

    }

    .slider-cards-container {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        grid-gap: 20px;
        margin-bottom: 20px;
    }

    .deliveryCards__addCard {
        grid-column: 1 / -1;
        /* Занимает все колонки */
        max-width: fit-content;
    }

    .review-cards-container {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        grid-gap: 20px;
        margin-bottom: 20px;
    }

    .add-faq-button {
        grid-column: 1 / -1;
        /* Занимает все колонки */
        max-width: fit-content;
    }



    /* #uploadWorkExamplesBtn{
        order: 10
    } */
</style>
<div class="dashboard-header">
    <nav class="navbar navbar-expand-lg bg-white fixed-top">
        <a class="navbar-brand" href="http://stylish-house.net/admin/">stylish-house.net</a>
        <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarSupportedContent"
            aria-controls="navbarSupportedContent" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse " id="navbarSupportedContent">
            <ul class="navbar-nav ml-auto navbar-right-top">
                <li class="nav-item">
                    <div id="custom-search" class="top-search-bar">
                        <input class="form-control" type="text" placeholder="Search.." value="">
                    </div>
                </li>
                <li class="nav-item dropdown notification">
                    <a class="nav-link nav-icons" href="#" id="navbarDropdownMenuLink1" data-toggle="dropdown"
                        aria-haspopup="true" aria-expanded="false"><i class="fas fa-fw fa-bell"></i> <span
                            class="indicator"></span></a>
                    <ul class="dropdown-menu dropdown-menu-right notification-dropdown">
                        <li>
                            <div class="notification-title"> Notification</div>
                            <div class="notification-list">
                                <div class="list-group">
                                    <a href="#" class="list-group-item list-group-item-action active">
                                        <div class="notification-info">
                                            <div class="notification-list-user-img"><img
                                                    src="{{ asset('/img/avatar-2.jpg') }}" alt=""
                                                    class="user-avatar-md rounded-circle"></div>
                                            <div class="notification-list-user-block"><span
                                                    class="notification-list-user-name">Jeremy
                                                    Rakestraw</span>accepted your invitation to join the team.
                                                <div class="notification-date">2 min ago</div>
                                            </div>
                                        </div>
                                    </a>
                                    <a href="#" class="list-group-item list-group-item-action">
                                        <div class="notification-info">
                                            <div class="notification-list-user-img"><img
                                                    src="{{ asset('/img/avatar-2.jpg') }}" alt=""
                                                    class="user-avatar-md rounded-circle"></div>
                                            <div class="notification-list-user-block"><span
                                                    class="notification-list-user-name">John
                                                    Abraham </span>is now following you
                                                <div class="notification-date">2 days ago</div>
                                            </div>
                                        </div>
                                    </a>
                                    <a href="#" class="list-group-item list-group-item-action">
                                        <div class="notification-info">
                                            <div class="notification-list-user-img"><img
                                                    src="{{ asset('/img/avatar-2.jpg') }}" alt=""
                                                    class="user-avatar-md rounded-circle"></div>
                                            <div class="notification-list-user-block"><span
                                                    class="notification-list-user-name">Monaan
                                                    Pechi</span> is watching your main repository
                                                <div class="notification-date">2 min ago</div>
                                            </div>
                                        </div>
                                    </a>
                                    <a href="#" class="list-group-item list-group-item-action">
                                        <div class="notification-info">
                                            <div class="notification-list-user-img"><img
                                                    src="{{ asset('/img/avatar-2.jpg') }}" alt=""
                                                    class="user-avatar-md rounded-circle"></div>
                                            <div class="notification-list-user-block"><span
                                                    class="notification-list-user-name">Jessica
                                                    Caruso</span>accepted your invitation to join the team.
                                                <div class="notification-date">2 min ago</div>
                                            </div>
                                        </div>
                                    </a>
                                </div>
                            </div>
                        </li>
                        <li>
                            <div class="list-footer"> <a href="#">View all notifications</a></div>
                        </li>
                    </ul>
                </li>
                <li class="nav-item dropdown connection">
                    <a class="nav-link" href="#" id="navbarDropdown" role="button" data-toggle="dropdown"
                        aria-haspopup="true" aria-expanded="false"> <i class="fas fa-fw fa-th"></i> </a>
                    <ul class="dropdown-menu dropdown-menu-right connection-dropdown">
                        <li class="connection-list">
                            <div class="row">
                                <div class="col-xl-4 col-lg-4 col-md-6 col-sm-12 col-12 ">
                                    <a href="#" class="connection-item"><img src="img/github.png" alt="">
                                        <span>Github</span></a>
                                </div>
                                <div class="col-xl-4 col-lg-4 col-md-6 col-sm-12 col-12 ">
                                    <a href="#" class="connection-item"><img src="img/dribbble.png"
                                            alt=""> <span>Dribbble</span></a>
                                </div>
                                <div class="col-xl-4 col-lg-4 col-md-6 col-sm-12 col-12 ">
                                    <a href="#" class="connection-item"><img src="img/dropbox.png"
                                            alt=""> <span>Dropbox</span></a>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-xl-4 col-lg-4 col-md-6 col-sm-12 col-12 ">
                                    <a href="#" class="connection-item"><img src="img/bitbucket.png"
                                            alt=""> <span>Bitbucket</span></a>
                                </div>
                                <div class="col-xl-4 col-lg-4 col-md-6 col-sm-12 col-12 ">
                                    <a href="#" class="connection-item"><img src="img/mail_chimp.png"
                                            alt=""><span>Mail chimp</span></a>
                                </div>
                                <div class="col-xl-4 col-lg-4 col-md-6 col-sm-12 col-12 ">
                                    <a href="#" class="connection-item"><img src="img/slack.png"
                                            alt=""> <span>Slack</span></a>
                                </div>
                            </div>
                        </li>
                        <li>
                            <div class="conntection-footer"><a href="#">More</a></div>
                        </li>
                    </ul>
                </li>
                <li class="nav-item dropdown nav-user">
                    <a class="nav-link nav-user-img" href="#" id="navbarDropdownMenuLink2"
                        data-toggle="dropdown" aria-haspopup="true" aria-expanded="false"><img
                            src="{{ asset('/img/avatar-2.jpg') }}" alt=""
                            class="user-avatar-md rounded-circle"></a>
                    <div class="dropdown-menu dropdown-menu-right nav-user-dropdown"
                        aria-labelledby="navbarDropdownMenuLink2">
                        <div class="nav-user-info">
                            <h5 class="mb-0 text-white nav-user-name">John Abraham </h5>
                            <span class="status"></span><span class="ml-2">Available</span>
                        </div>
                        <a class="dropdown-item" href="#"><i class="fas fa-user mr-2"></i>Account</a>
                        <a class="dropdown-item" href="#"><i class="fas fa-cog mr-2"></i>Setting</a>
                        {{-- <a class="dropdown-item" href="{{ route('logout') }}"><i
                                class="fas fa-power-off mr-2"></i>Logout</a> --}}
                                <form id="logout-form" action="{{ route('logout') }}" method="POST" style="display: none;">
                                    @csrf
                                </form>
                                <button class="dropdown-item" onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
                                 <span>  Logout
                                </button>
                    </div>
                </li>
            </ul>
        </div>
    </nav>
</div>
<meta name="csrf-token" content="{{ csrf_token() }}">