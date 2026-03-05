<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Application Language Lines
    |--------------------------------------------------------------------------
    |
    | The following language lines are used by the paginator library to build
    | the simple pagination links. You are free to change them to anything
    | you want to customize your views to better match your application.
    |
    */

    'errors'        => [
        '401'    => [
            'title'   => 'Không được phép',
            'message' => 'Bạn cần đăng nhập để truy cập trang này.',
            'login'   => 'Đăng nhập',
            'back'    => 'Quay lại',
        ],
        '403'    => [
            'title'   => 'Bị cấm',
            'message' => 'Bạn không có quyền truy cập trang này.',
            'back'    => 'Quay lại',
            'home'    => 'Về trang chủ',
            'logout'  => 'Đăng xuất',
        ],
        '404'    => [
            'title'   => 'Không tìm thấy trang',
            'message' => 'Trang bạn đang tìm kiếm không tồn tại.',
            'back'    => 'Quay lại',
            'home'    => 'Về trang chủ',
        ],
        '419'    => [
            'title'         => 'Trang đã hết hạn',
            'message'       => 'Phiên đăng nhập của bạn đã hết hạn. Vui lòng đăng nhập lại để tiếp tục.',
            'back_to_login' => 'Quay lại trang đăng nhập',
        ],
        '429'    => [
            'title'   => 'Quá nhiều yêu cầu',
            'message' => 'Bạn đã gửi quá nhiều yêu cầu. Vui lòng thử lại sau.',
            'back'    => 'Quay lại',
            'refresh' => 'Thử lại',
        ],
        '500'    => [
            'title'   => 'Lỗi máy chủ',
            'message' => 'Đã xảy ra lỗi trên máy chủ của chúng tôi. Chúng tôi đang khắc phục vấn đề.',
            'back'    => 'Quay lại',
            'home'    => 'Về trang chủ',
            'report'  => 'Báo cáo vấn đề',
        ],
        '503'    => [
            'title'   => 'Dịch vụ không khả dụng',
            'message' => 'Dịch vụ tạm thời không khả dụng. Vui lòng thử lại sau.',
            'back'    => 'Quay lại',
            'refresh' => 'Làm mới trang',
        ],
        'common' => [
            'go_back' => 'Quay lại',
            'go_home' => 'Về trang chủ',
            'login'   => 'Đăng nhập',
            'logout'  => 'Đăng xuất',
            'refresh' => 'Làm mới trang',
        ],
    ],

    'data_deletion' => [
        'title'              => 'Yêu Cầu Xóa Dữ Liệu',
        'page_title'         => 'Yêu Cầu Xóa Dữ Liệu',
        'success_page_title' => 'Yêu Cầu Đã Được Gửi Thành Công',
        'success_title'      => 'Yêu Cầu Đã Được Gửi Thành Công',
        'send_request'       => 'Gửi Yêu Cầu',
        'request_submitted'  => 'Yêu cầu xóa dữ liệu của bạn đã được tiếp nhận',
        'back_to_form'       => 'Quay Lại Form',
        'back_to_policy'     => 'Quay Lại Chính Sách Bảo Mật',
        'description_1'      => 'Nếu bạn muốn yêu cầu xóa dữ liệu cá nhân của mình khỏi hệ thống, bạn có thể gửi yêu cầu xóa dữ liệu bằng cách nhấp vào nút bên dưới.',
        'description_2'      => 'Sau khi yêu cầu của bạn được gửi, đội ngũ của chúng tôi sẽ xem xét và xử lý theo chính sách bảo mật của chúng tôi. Bạn sẽ nhận được email xác nhận sau khi yêu cầu của bạn đã được xử lý.',
        'description_3'      => 'Xin lưu ý rằng quá trình này có thể mất một khoảng thời gian để hoàn thành, và tất cả dữ liệu cá nhân của bạn sẽ bị xóa vĩnh viễn khỏi hệ thống của chúng tôi sau khi yêu cầu của bạn được chấp thuận.',
        'modal_title'        => 'Yêu Cầu Xóa Dữ Liệu',
        'modal_message'      => 'Bạn có chắc chắn muốn yêu cầu xóa dữ liệu của mình không? Hành động này không thể hoàn tác.',
        'modal_cancel'       => 'Hủy',
        'success_message'    => 'Yêu cầu của bạn đã được gửi thành công. Chúng tôi sẽ xử lý trong thời gian sớm nhất.',
    ],

    'policy'        => [
        'title'               => 'Chính Sách Bảo Mật',
        'page_title'          => 'Chính Sách Bảo Mật',
        'last_updated'        => 'Cập nhật lần cuối',
        'content_unavailable' => 'Nội dung không khả dụng',
        'updating_message'    => 'Chúng tôi đang cập nhật chính sách bảo mật. Vui lòng quay lại sau.',
    ],
];
