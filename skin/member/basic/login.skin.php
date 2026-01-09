<?php
if (!defined('_GNUBOARD_')) exit; // 개별 페이지 접근 불가

$sidepanel_theme_url = defined('G5_THEME_URL') ? G5_THEME_URL : (G5_URL . '/theme/sidepanel');
add_stylesheet('<link rel="stylesheet" href="'.$sidepanel_theme_url.'/style.css">', 0);
add_stylesheet('<link rel="stylesheet" href="'.$member_skin_url.'/style.css">', 1);
$login_title = (isset($g5['title']) && trim($g5['title'])) ? $g5['title'] : '로그인';
$sp_logo = G5_URL . '/assets/img/common/logo.png';
?>

<style>
.sp-login-wrap {
    max-width: 560px;
    margin: 80px auto;
    padding: 0 22px;
    --brand-brown: #322823;
    --brand-green: #87b145;
    --brand-ink: #1f1916;
    --brand-line: #e2e6ea;
    --brand-wash: #f7f8fa;
    --brand-focus: rgba(31, 25, 22, 0.12);
}
.sp-login-card {
    background: linear-gradient(180deg, #ffffff 0%, var(--brand-wash) 100%);
    border: 1px solid var(--brand-line);
    border-radius: 22px;
    padding: 42px 46px 36px;
    box-shadow: 0 14px 30px rgba(15, 23, 42, 0.08);
    font-family: "Pretendard","Noto Sans KR",system-ui,sans-serif;
    position: relative;
    overflow: hidden;
}
.sp-login-card::before {
    content: "";
    position: absolute;
    inset: 0 0 auto 0;
    height: 4px;
    background: linear-gradient(90deg, var(--brand-green), rgba(135, 177, 69, 0));
}
.sp-login-brand { display: flex; align-items: center; gap: 16px; margin-bottom: 26px; flex-wrap: wrap; }
.sp-login-brand img { height: 44px; width: auto; display: block; }
.sp-login-title {
    margin: 0;
    font-size: 28px;
    font-weight: 800;
    color: var(--brand-brown);
    letter-spacing: -0.01em;
    text-align: left;
    position: relative;
}
.sp-login-title::after {
    content: "";
    display: block;
    width: 36px;
    height: 4px;
    background: var(--brand-green);
    border-radius: 999px;
    margin-top: 8px;
}
.sp-login-form { display: grid; margin-top: 6px; }
.sp-field { display: flex; flex-direction: column; gap: 8px; margin-bottom: 18px; }
.sp-field label { font-weight: 800; color: var(--brand-ink); font-size: 16px; letter-spacing: -0.005em; text-align: left; }
.sp-field input {
    border: 1px solid #dfe3e8;
    border-radius: 12px;
    padding: 14px 15px;
    font-size: 15px;
    font-family: "Pretendard","Noto Sans KR",system-ui,sans-serif;
    transition: border-color 0.16s ease, box-shadow 0.16s ease;
    text-align: left;
    background: #fff;
    color: #1f1916;
}
.sp-field input::placeholder { color: #9aa3ad; }
.sp-field input:focus { outline: none; border-color: var(--brand-green); box-shadow: 0 0 0 3px var(--brand-focus); }
.sp-actions { display: flex; align-items: center; gap: 16px; flex-wrap: wrap; justify-content: flex-start;  }
.sp-remember { display: inline-flex; align-items: center; gap: 10px; cursor: pointer; color: #2a2320; font-weight: 700; user-select: none; }
.sp-remember input { display: none; }
.sp-remember .box {
    width: 18px; height: 18px; border: 1px solid #c8cdd3; border-radius: 5px;
    display: inline-flex; align-items: center; justify-content: center; position: relative; background: #fff;
}
.sp-remember .box::after {
    content: ""; position: absolute; width: 6px; height: 10px; border: 2px solid #fff;
    border-top: 0; border-left: 0; transform: rotate(42deg); top: 1px; left: 5px; opacity: 0;
}
.sp-remember input:checked + .box { border-color: var(--brand-green); background: var(--brand-green); }
.sp-remember input:checked + .box::after { opacity: 1; }
.sp-remember .text { font-size: 14px; font-weight: 500;}
.sp-submit {
    width: 100%; margin-top: 40px; padding: 15px 16px; border-radius: 12px;
    border: 1px solid #2a211d; background: linear-gradient(135deg, #3a2f28 0%, #322823 55%, #4b3a31 100%); color: #fff;
    font-weight: 800; font-size: 16px; cursor: pointer; box-shadow: 0 12px 24px rgba(28, 22, 19, 0.2);
    transition: transform 0.18s ease, box-shadow 0.18s ease, filter 0.18s ease;
    text-align: center;
    font-family: "Pretendard","Noto Sans KR",system-ui,sans-serif;
}
.sp-submit:hover, .sp-submit:focus-visible {
    transform: translateY(-1px);
    box-shadow: 0 16px 30px rgba(28, 22, 19, 0.2), 0 8px 18px rgba(0, 0, 0, 0.08);
    filter: brightness(1.02);
    outline: none;
}
.sp-login-card #sns_login { margin-top: 16px; border-color: var(--brand-line); }
@media (max-width: 640px) {
    .sp-login-card { padding: 32px 22px; }
    .sp-login-title { font-size: 24px; }
    .sp-login-brand { gap: 12px; }
}
</style>

<div id="mb_login" class="sp-login-wrap">
    <div class="sp-login-card">
        <div class="sp-login-brand">
            <img src="<?php echo $sp_logo; ?>" alt="Genebiotech">
            <div>
                <p class="sp-login-title"><?php echo $login_title; ?></p>
            </div>
        </div>

        <form name="flogin" action="<?php echo $login_action_url ?>" onsubmit="return flogin_submit(this);" method="post" class="sp-login-form">
            <input type="hidden" name="url" value="<?php echo $login_url ?>">

            <div class="sp-field">
                <label for="login_id">아이디</label>
                <input type="text" name="mb_id" id="login_id" required class="frm_input required" size="20" maxLength="20" placeholder="아이디">
            </div>

            <div class="sp-field">
                <label for="login_pw">비밀번호</label>
                <input type="password" name="mb_password" id="login_pw" required class="frm_input required" size="20" maxLength="20" placeholder="비밀번호">
            </div>

        <div class="sp-actions">
            <label class="sp-remember" for="login_auto_login">
                <input type="checkbox" name="auto_login" id="login_auto_login" class="selec_chk">
                <span class="box"></span>
                <span class="text">자동 로그인</span>
            </label>
        </div>

            <button type="submit" class="sp-submit">로그인</button>
        </form>

        <?php @include_once(get_social_skin_path().'/social_login.skin.php'); ?>
    </div>

    <?php // 비회원 주문/조회 영역은 기존 구조 유지 ?>
    <?php if (isset($default['de_level_sell']) && $default['de_level_sell'] == 1) { ?>

    <?php if (preg_match("/orderform.php/", $url)) { ?>
    <section id="mb_login_notmb">
        <h2>비회원 구매</h2>
        <p>비회원으로 주문하시는 경우 포인트는 적립되지 않습니다.</p>

        <div id="guest_privacy">
            <?php echo conv_content($default['de_guest_privacy'], $config['cf_editor']); ?>
        </div>
		
		<div class="chk_box">
			<input type="checkbox" id="agree" value="1" class="selec_chk">
        	<label for="agree"><span></span> 개인정보수집에 대한 내용을 읽었으며 이에 동의합니다.</label>
		</div>
		
        <div class="btn_confirm">
            <a href="javascript:guest_submit(document.flogin);" class="btn_submit">비회원으로 구매하기</a>
        </div>

        <script>
        function guest_submit(f)
        {
            if (document.getElementById('agree')) {
                if (!document.getElementById('agree').checked) {
                    alert("개인정보수집에 대한 내용을 읽고 이에 동의해 주십시오.");
                    return;
                }
            }

            f.url.value = "<?php echo $url; ?>";
            f.action = "<?php echo $url; ?>";
            f.submit();
        }
        </script>
    </section>

    <?php } else if (preg_match("/orderinquiry.php$/", $url)) { ?>
    <div id="mb_login_od_wr">
        <h2>비회원 주문조회 </h2>

        <fieldset id="mb_login_od">
            <legend>비회원 주문조회</legend>

            <form name="forderinquiry" method="post" action="<?php echo urldecode($url); ?>" autocomplete="off">

            <label for="od_id" class="od_id sound_only">주문번호<strong class="sound_only"> 필수</strong></label>
            <input type="text" name="od_id" value="<?php echo $od_id; ?>" id="od_id" required class="frm_input required" size="20" placeholder="주문번호">
            <label for="od_pwd" class="od_pwd sound_only">비밀번호 <strong>필수</strong></label>
            <input type="password" name="od_pwd" size="20" id="od_pwd" required class="frm_input required" placeholder="비밀번호">
            <button type="submit" class="btn_submit">확인</button>

            </form>
        </fieldset>

        <section id="mb_login_odinfo">
            <p>메일로 발송된 주문서의 <strong>주문번호</strong> 와 주문 시 입력하신 <strong>비밀번호</strong>를 확인해 주세요.</p>
        </section>

    </div>
    <?php } ?>

    <?php } ?>
</div>

<script>
jQuery(function($){
    $("#login_auto_login").click(function(){
        if (this.checked) {
            this.checked = confirm("자동로그인을 사용하시면 다음부터는 비밀번호 없이 로그인됩니다.\n\n공공장소에서는 개인정보가 노출될 수 있으므로 사용을 자제해 주세요.");
        }
    });
});

function flogin_submit(f)
{
    if( $( document.body ).triggerHandler( 'login_sumit', [f, 'flogin'] ) !== false ){
        return true;
    }
    return false;
}
</script>
