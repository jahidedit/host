<?php
session_start();
include 'conf.php';

$svc_no = $_SESSION['user'];
$sql = "SELECT * FROM users WHERE service_number = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $svc_no);
$stmt->execute();
$result = $stmt->get_result();

if($result->num_rows === 1) {
    $current_user = $result->fetch_assoc();
    $rank = $current_user['rank'];
    $name = $current_user['name'];
    $full_name = $rank." ".$name;
    $email = $current_user['email'];
    $phone = $current_user['phone'];
    $service_number = $current_user['service_number'];
} else {
    session_unset();
    session_destroy();
    header("Location: login.php");
    exit();
}
// গ্রাভাটার ইউআরএল
function gravatar_url($email, $size = 120) {
    $hash = md5(strtolower(trim($email)));
    return "https://www.gravatar.com/avatar/$hash?s=$size&d=identicon";
}
?>


<!DOCTYPE html>
<html lang="en">

<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>MT User Profile</title>
	<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
	<style>
	:root {
		/* Light Mode Colors */
		--primary-color: #3498db;
		--secondary-color: #2980b9;
		--text-color: #333;
		--light-gray: #f5f5f5;
		--medium-gray: #e0e0e0;
		--dark-gray: #777;
		--white: #ffffff;
		--gold: #FFD700;
		--success-color: #4CAF50;
		--error-color: #f44336;
		--warning-color: #ff9800;
		--tgl: #303030;
		--tgl-s: #5bfff0;
		/* Current Mode Variables */
		--primary: var(--primary-color);
		--secondary: var(--secondary-color);
		--text: var(--text-color);
		--light: var(--light-gray);
		--medium: var(--medium-gray);
		--dark: var(--dark-gray);
		--bg-color: var(--white);
		--card-bg: var(--white);
		--shadow-color: rgba(0, 0, 0, 0.1);
	}

	.dark-mode {
		--primary-color: #1e88e5;
		--secondary-color: #1565c0;
		--text-color: #f5f5f5;
		--light-gray: #121212;
		--medium-gray: #333;
		--dark-gray: #aaa;
		--white: #1e1e1e;
		--gold: #FFD700;
		--tgl: #5a21bf;
		--tgl-s: #14ff00;
		--primary: var(--primary-color);
		--secondary: var(--secondary-color);
		--text: var(--text-color);
		--light: var(--light-gray);
		--medium: var(--medium-gray);
		--dark: var(--dark-gray);
		--bg-color: var(--light-gray);
		--card-bg: var(--white);
		--shadow-color: rgba(0, 0, 0, 0.3);
	}

	* {
		margin: 0;
		padding: 0;
		box-sizing: border-box;
		font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
		transition: background-color 0.3s, color 0.3s;
	}

	body {
		min-height: 100vh;
		background-color: var(--bg-color);
		color: var(--text);
	}

	/* Desktop Layout */
	.dashboard {
		width: 95%;
		max-width: 1400px;
		min-height: ;
		background: var(--card-bg);
		border-radius: 15px;
		overflow: hidden;
		box-shadow: 0 10px 30px var(--shadow-color);
		border: 1px solid var(--medium);
		display: grid;
		grid-template-columns: 300px 1fr;
		position: relative;
		margin: 20px auto;
		height: auto;
		max-height: 95%;
	}

	.sidebar {
		background: var(--primary);
		display: flex;
		flex-direction: column;
		border-right: 1px solid var(--medium);
		overflow: hidden;
		height: 95vh;
	}

	.sidebarscroll {
		overflow-y: auto;
		padding: 30px 20px;
		padding-top: 0;
	}

	.profile {
		text-align: center;
		margin-bottom: 5px;
		color: white;
		padding: 25px 20px;
		padding-bottom: 25px;
		padding-bottom: 10px;
	}

	.avatar {
		width: 150px;
		height: 150px;
		border-radius: 50%;
		object-fit: cover;
		border: 3px solid var(--gold);
		box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
		margin-bottom: 15px;
		cursor: pointer;
	}

	.profile-name {
		font-size: 1.3rem;
		font-weight: 600;
		margin-bottom: 5px;
	}

	.profile-rank {
		font-size: 0.9rem;
		color: var(--gold);
		margin-bottom: 15px;
	}

	.profile-service {
		font-size: 0.8rem;
		color: rgba(255, 255, 255, 0.7);
	}

	.nav-menu {
		width: 100%;
	}

	.nav-item {
		display: flex;
		align-items: center;
		padding: 12px 15px;
		margin-bottom: 10px;
		border-radius: 8px;
		color: white;
		text-decoration: none;
		transition: all 0.3s ease;
		cursor: pointer;
		background: none;
		border: none;
		width: 100%;
		text-align: left;
		font-size: 1rem;
	}

	.nav-item:hover,
	.nav-item:focus,
	.nav-item.active {
		background: rgba(255, 255, 255, 0.2);
		color: white;
		outline: none;
	}

	.nav-item:focus-visible {
		box-shadow: 0 0 0 2px var(--white);
		outline: none;
	}

	.nav-item i {
		margin-right: 10px;
		font-size: 1.1rem;
		width: 20px;
		text-align: center;
	}

	.nav-home {
		padding: 15px 30px;
		border-bottom: 2px solid var(--shadow-color);
		border-radius: unset;
	}

	/* Quick Actions Section */
	.quick-actions-section {
		width: 100%;
	}

	.quick-actions-title {
		display: flex;
		align-items: center;
		padding: 10px 15px;
		color: white;
		cursor: pointer;
		border-radius: 8px;
		transition: all 0.3s ease;
	}

	.quick-actions-title:hover {
		background: rgba(255, 255, 255, 0.1);
	}

	.quick-actions-title i {
		transition: transform 0.3s;
	}

	.quick-actions-title.collapsed i {
		transform: rotate(-90deg);
	}

	.quick-actions-content {
		overflow: hidden;
		max-height: 0;
		transition: max-height 0.3s ease;
	}

	.quick-actions-content.expanded {
		max-height: 500px;
	}

	.drpicon {
		margin-left: 20px;
	}

	.notification-container {
		position: relative;
		display: inline-block;
	}

	.notification-badge {
		position: absolute;
		top: -5px;
		right: -1px;
		background-color: #ff5252;
		color: white;
		border-radius: 50%;
		width: 20px;
		height: 20px;
		display: flex;
		align-items: center;
		justify-content: center;
		font-size: 12px;
		font-weight: bold;
	}

	/* Quick Actions Grid */
	.quick-actions-grid {
		display: grid;
		grid-template-columns: repeat(auto-fill, minmax(120px, 1fr));
		gap: 15px;
		padding: 10px 0;
	}

	/* Action Buttons*/
	.action-button {
		display: flex;
		flex-direction: column;
		align-items: center;
		justify-content: center;
		padding: 20px 10px;
		background-color: var(--card-bg);
		color: var(--primary);
		border: 1px solid var(--medium);
		border-radius: 8px;
		cursor: pointer;
		transition: all 0.3s ease;
		text-align: center;
		height: 100%;
		z-index: 1;
	}

	.action-button:hover {
		background-color: var(--primary);
		color: white;
		border-color: var(--primary);
		transform: translateY(-3px);
		box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
	}

	.action-button i {
		font-size: 24px;
		margin-bottom: 10px;
	}

	.action-button span {
		font-size: 14px;
		font-weight: 600;
	}

	.logout-button {
		background-color: #ff5252;
		color: white;
		border-color: #ff5252;
	}

	.logout-button:hover {
		background-color: #e53935;
		border-color: #e53935;
	}

	.main-content {
		padding: 30px;
		overflow-y: auto;
		background-color: var(--light);
		color: var(--text);
	}

	.header {
		display: flex;
		justify-content: space-between;
		align-items: center;
		margin-bottom: 30px;
		padding-bottom: 15px;
		border-bottom: 1px solid var(--medium);
	}

	.page-title {
		font-size: 1.5rem;
		font-weight: 600;
		color: var(--text);
	}

	.header-actions {
		display: flex;
		align-items: center;
	}

	.home-button {
		padding: 8px 16px;
		background-color: var(--primary);
		color: white;
		border: none;
		border-radius: 4px;
		cursor: pointer;
		font-size: 14px;
		display: flex;
		align-items: center;
		gap: 8px;
		transition: all 0.2s ease;
		margin-right: 10px;
	}

	.home-button:hover {
		background-color: var(--secondary);
	}

	/* New Toggle Switch Styles */
	.checkbox {
		opacity: 0;
		position: absolute;
	}

	.checkbox-label {
		background-color: var(--tgl);
		width: 50px;
		height: 26px;
		border-radius: 50px;
		position: relative;
		padding: 5px;
		cursor: pointer;
		display: flex;
		justify-content: space-between;
		align-items: center;
	}

	.checkbox-label .fa-moon {
		color: #f1c40f;
		font-size: 16px;
	}

	.checkbox-label .fa-sun {
		color: #f39c12;
		font-size: 15px;
	}

	.checkbox-label .ball {
		background-color: var(--tgl-s);
		width: 22px;
		height: 22px;
		position: absolute;
		left: 2px;
		top: 2px;
		border-radius: 50%;
		transition: transform 0.2s linear;
	}

	.checkbox:checked+.checkbox-label .ball {
		transform: translateX(24px);
	}

	/* Remove the old toggle switch styles */
	.toggle-switch,
	.slider,
	.toggle-icon {
		display: none;
	}

	.card {
		background: var(--card-bg);
		border-radius: 10px;
		padding: 20px;
		margin-bottom: 20px;
		box-shadow: 0 5px 15px var(--shadow-color);
		border: 1px solid var(--medium);
	}

	.card-title {
		font-size: 1.2rem;
		margin-bottom: 15px;
		color: var(--primary);
		display: flex;
		align-items: center;
	}

	.card-title i {
		margin-right: 10px;
	}

	.info-grid {
		display: grid;
		grid-template-columns: repeat(2, 1fr);
		gap: 15px;
	}

	.info-item {
		margin-bottom: 10px;
	}

	.info-label {
		font-size: 0.8rem;
		color: var(--dark);
		margin-bottom: 5px;
	}

	.info-value {
		font-size: 1rem;
		font-weight: 500;
	}

	/* Mobile Layout */
	.mobile-container {
		display: none;
		width: 100%;
		max-width: 100%;
		padding: 0;
		margin: 0 auto;
		background-color: var(--light);
	}

	.mobile-profile-header {
		position: relative;
		display: flex;
		align-items: center;
		padding: 20px;
		background: linear-gradient(135deg, var(--primary), var(--secondary));
		color: white;
		gap: 20px;
		overflow: hidden;
	}

	.mobile-profile-photo {
		width: 80px;
		height: 80px;
		border-radius: 50%;
		object-fit: cover;
		border: 3px solid var(--gold);
		box-shadow: 0 4px 10px rgba(0, 0, 0, 0.2);
		flex-shrink: 0;
		cursor: pointer;
		z-index: 1;
	}

	.mobile-profile-info {
		flex-grow: 1;
	}

	.mobile-profile-name {
		font-size: 1.2rem;
		font-weight: 700;
		margin-bottom: 5px;
		text-shadow: 0 1px 3px rgba(0, 0, 0, 0.2);
	}

	.mobile-profile-rank {
		font-size: 1rem;
		opacity: 0.9;
		font-weight: 600;
		margin-bottom: 5px;
		display: inline-block;
		padding: 3px 10px;
		background: rgba(0, 0, 0, 0.2);
		border-radius: 15px;
	}

	/* Background Decorations */
	.bg-deco {
		position: absolute;
		z-index: 0;
		pointer-events: none;
		border-radius: 50%;
		filter: blur(50px);
		opacity: 0.5;
		animation: float 10s ease-in-out infinite;
	}

	.blob1 {
		width: 200px;
		height: 200px;
		background: radial-gradient(circle, #ff00cc, #3333ff);
		top: -80px;
		left: -80px;
		animation-delay: 0s;
	}

	.blob2 {
		width: 150px;
		height: 150px;
		background: radial-gradient(circle, #00ffe7, #00ff88);
		bottom: -60px;
		right: -60px;
		animation-delay: 3s;
	}

	.blob3 {
		width: 100px;
		height: 100px;
		background: radial-gradient(circle, #ffae00, #ff0080);
		top: 40%;
		left: 60%;
		transform: translate(-50%, -50%);
		animation-delay: 5s;
	}

	.bg-stripe {
		width: 100%;
		height: 4px;
		background: linear-gradient(to right, #38bdf8, #a855f7, #ec4899, #facc15);
		position: absolute;
		top: 0;
		left: 0;
		opacity: 0.4;
		filter: blur(2px);
		border-radius: 999px;
	}

	@keyframes float {

		0%,
		100% {
			transform: translateY(0) scale(1);
		}

		50% {
			transform: translateY(-15px) scale(1.05);
		}
	}

	.bubble-container {
		position: absolute;
		top: 0;
		left: 0;
		height: 100%;
		width: 100%;
		overflow: hidden;
		z-index: 0;
		pointer-events: none;
	}

	.bubble {
		position: absolute;
		border-radius: 50%;
		opacity: 0.5;
		filter: blur(1.5px) brightness(1.3);
		background: radial-gradient(circle at 30% 30%, rgba(255, 255, 255, 0.8), rgba(0, 0, 0, 0.1));
		animation: floatAround 15s linear infinite;
		animation-delay: var(--delay);
	}

	/* Smaller bubbles: now between 40px and 80px */
	.bubble:nth-child(1) {
		width: 50px;
		height: 50px;
		top: 20%;
		left: 10%;
		background: rgba(255, 0, 128, 0.4);
		--delay: 0s;
	}

	.bubble:nth-child(2) {
		width: 60px;
		height: 60px;
		top: 60%;
		left: 5%;
		background: rgba(0, 255, 255, 0.3);
		--delay: 0.3s;
	}

	.bubble:nth-child(3) {
		width: 45px;
		height: 45px;
		top: 40%;
		left: 70%;
		background: rgba(0, 255, 128, 0.3);
		--delay: 0.6s;
	}

	.bubble:nth-child(4) {
		width: 70px;
		height: 70px;
		top: 80%;
		left: 60%;
		background: rgba(255, 128, 0, 0.3);
		--delay: 0.9s;
	}

	.bubble:nth-child(5) {
		width: 40px;
		height: 40px;
		top: 50%;
		left: 85%;
		background: rgba(128, 0, 255, 0.3);
		--delay: 1.2s;
	}

	.bubble:nth-child(6) {
		width: 60px;
		height: 60px;
		top: 25%;
		left: 90%;
		background: rgba(0, 128, 255, 0.3);
		--delay: 1.5s;
	}

	.bubble:nth-child(7) {
		width: 50px;
		height: 50px;
		top: 10%;
		left: 40%;
		background: rgba(255, 255, 0, 0.3);
		--delay: 1.8s;
	}

	.bubble:nth-child(8) {
		width: 45px;
		height: 45px;
		top: 70%;
		left: 30%;
		background: rgba(255, 0, 255, 0.3);
		--delay: 2.1s;
	}

	.bubble:nth-child(9) {
		width: 65px;
		height: 65px;
		top: 55%;
		left: 50%;
		background: rgba(0, 255, 200, 0.3);
		--delay: 2.4s;
	}

	.bubble:nth-child(10) {
		width: 55px;
		height: 55px;
		top: 35%;
		left: 20%;
		background: rgba(200, 255, 0, 0.3);
		--delay: 2.7s;
	}

	@keyframes floatAround {
		0% {
			transform: translate(0, 0) scale(1);
			opacity: 0;
		}

		10% {
			opacity: 0.5;
		}

		50% {
			transform: translate(var(--x, 100px), var(--y, -100px)) scale(1.15);
			opacity: 0.7;
		}

		100% {
			transform: translate(calc(var(--x, 100px) * -1), calc(var(--y, 100px) * -1)) scale(1);
			opacity: 0;
		}
	}

	/* Mobile Tabs Navigation */
	.mobile-tabs-container {
		position: sticky;
		top: 0;
		z-index: 100;
		background-color: var(--card-bg);
		padding-top: 5px;
		box-shadow: 0 2px 5px var(--shadow-color);
		border-bottom: 2px solid var(--medium);
	}

	.mobile-tabs-scroll {
		display: flex;
		overflow-x: auto;
		scroll-behavior: smooth;
		-webkit-overflow-scrolling: touch;
		scrollbar-width: none;
		padding: 5px 15px;
	}

	.mobile-tabs-scroll::-webkit-scrollbar {
		display: none;
	}

	.mobile-tab-button {
		padding: 10px 15px;
		background: none;
		border: none;
		cursor: pointer;
		font-size: 14px;
		font-weight: 600;
		color: var(--dark);
		position: relative;
		white-space: nowrap;
		flex-shrink: 0;
		text-decoration: none;
	}

	.mobile-tab-button:hover {
		color: var(--primary);
	}

	.mobile-tab-button.active {
		color: var(--primary);
	}

	.mobile-tab-button.active::after {
		content: '';
		position: absolute;
		bottom: -1px;
		left: 0;
		width: 100%;
		height: 3px;
		background-color: var(--primary);
	}

	/* Mobile Content */
	.mobile-content {
		padding: 10px 5px;
		background-color: var(--light);
	}

	.mobile-tab-content {
		display: none;
	}

	.mobile-tab-content.active {
		display: block;
	}

	.scroll-indicator {
		position: absolute;
		top: 0;
		height: 100%;
		width: 30px;
		display: flex;
		align-items: center;
		justify-content: center;
		background: linear-gradient(90deg, var(--light), rgba(245, 248, 250, 0));
		z-index: 1;
		pointer-events: none;
		opacity: 0;
		transition: opacity 0.3s;
	}

	.scroll-indicator.left {
		left: 0;
	}

	.scroll-indicator.right {
		right: 0;
		background: linear-gradient(270deg, var(--light), rgba(245, 248, 250, 0));
	}

	.scroll-indicator i {
		color: var(--primary);
		font-size: 18px;
		background-color: var(--light);
		border-radius: 50%;
		width: 24px;
		height: 24px;
		display: flex;
		align-items: center;
		justify-content: center;
		box-shadow: 0 2px 4px var(--shadow-color);
		pointer-events: auto;
		cursor: pointer;
	}

	/* Profile Photo Modal */
	.profile-photo-modal {
		display: none;
		position: fixed;
		top: 0;
		left: 0;
		width: 100%;
		height: 100%;
		z-index: 1000;
	}

	.photo-modal-overlay {
		position: absolute;
		top: 0;
		left: 0;
		width: 100%;
		height: 100%;
		background-color: rgba(0, 0, 0, 0.5);
	}

	/* Desktop modal content */
	.photo-modal-content {
		position: absolute;
		background-color: var(--card-bg);
		padding: 15px;
		border-radius: 8px;
		z-index: 1001;
		box-shadow: 0 4px 12px var(--shadow-color);
		display: flex;
		flex-direction: column;
		align-items: center;
	}

	/* Fullscreen photo view */
	.photo-fullscreen {
		position: fixed;
		top: 0;
		left: 0;
		width: 100%;
		height: 100%;
		display: flex;
		align-items: center;
		justify-content: center;
		z-index: 1002;
		background-color: rgba(0, 0, 0, 0.9);
	}

	.photo-fullscreen img {
		max-width: 90%;
		max-height: 90%;
		object-fit: contain;
	}

	.close-fullscreen {
		position: absolute;
		top: 20px;
		right: 20px;
		color: white;
		font-size: 30px;
		cursor: pointer;
		z-index: 1003;
	}

	.photo-options {
		display: flex;
		gap: 10px;
	}

	.photo-option-btn {
		padding: 8px 16px;
		background-color: var(--primary);
		color: white;
		border: none;
		border-radius: 4px;
		cursor: pointer;
		transition: all 0.2s ease;
		font-size: 14px;
	}

	.photo-option-btn:hover {
		background-color: var(--secondary);
	}

	/* Form Modals */
	.form-modal {
		display: none;
		position: fixed;
		top: 0;
		left: 0;
		width: 100%;
		height: 100%;
		background-color: rgba(0, 0, 0, 0.5);
		z-index: 1100;
		justify-content: center;
		align-items: center;
	}

	.form-modal-overlay {
		position: absolute;
		top: 0;
		left: 0;
		width: 100%;
		height: 100%;
		z-index: 1;
	}

	.modal-content-form {
		background-color: var(--card-bg);
		padding: 25px;
		border-radius: 10px;
		width: 100%;
		max-width: 500px;
		box-shadow: 0 5px 15px var(--shadow-color);
		position: relative;
		max-height: 90vh;
		overflow-y: auto;
		z-index: 2;
	}

	.modal-header {
		display: flex;
		justify-content: space-between;
		align-items: center;
		margin-bottom: 20px;
		padding-bottom: 10px;
		border-bottom: 1px solid var(--medium);
	}

	.modal-title {
		font-size: 22px;
		font-weight: 600;
		color: var(--primary);
	}

	.close-modal {
		background: none;
		border: none;
		font-size: 24px;
		cursor: pointer;
		color: var(--text);
		transition: color 0.2s;
	}

	.close-modal:hover {
		color: var(--primary);
	}

	.form-group {
		margin-bottom: 20px;
	}

	.form-label {
		display: block;
		margin-bottom: 8px;
		font-weight: 600;
		color: var(--text);
	}

	.form-control {
		width: 100%;
		padding: 10px 15px;
		border: 1px solid var(--medium);
		border-radius: 6px;
		font-size: 16px;
		transition: border-color 0.3s;
		background-color: var(--card-bg);
		color: var(--text);
	}

	.form-control:focus {
		border-color: var(--primary);
		outline: none;
	}

	.form-select {
		width: 100%;
		padding: 10px 15px;
		border: 1px solid var(--medium);
		border-radius: 6px;
		font-size: 16px;
		appearance: none;
		background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='16' height='16' fill='%23777' viewBox='0 0 16 16'%3E%3Cpath d='M7.247 11.14 2.451 5.658C1.885 5.013 2.345 4 3.204 4h9.592a1 1 0 0 1 .753 1.659l-4.796 5.48a1 1 0 0 1-1.506 0z'/%3E%3C/svg%3E");
		background-repeat: no-repeat;
		background-position: right 15px center;
		background-size: 16px;
		background-color: var(--card-bg);
		color: var(--text);
	}

	.dark-mode .form-select {
		background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='16' height='16' fill='%23aaa' viewBox='0 0 16 16'%3E%3Cpath d='M7.247 11.14 2.451 5.658C1.885 5.013 2.345 4 3.204 4h9.592a1 1 0 0 1 .753 1.659l-4.796 5.48a1 1 0 0 1-1.506 0z'/%3E%3C/svg%3E");
	}

	.form-select:focus {
		border-color: var(--primary);
		outline: none;
	}

	.btn {
		padding: 10px 20px;
		border: none;
		border-radius: 6px;
		font-size: 16px;
		font-weight: 600;
		cursor: pointer;
		transition: all 0.3s ease;
	}

	.btn-primary {
		background-color: var(--primary);
		color: white;
	}

	.btn-primary:hover {
		background-color: var(--secondary);
	}

	.btn-block {
		display: block;
		width: 100%;
	}

	.password-toggle {
		position: relative;
	}

	.password-toggle-icon {
		position: absolute;
		right: 10px;
		top: 50%;
		transform: translateY(-50%);
		cursor: pointer;
		color: var(--text);
		background: none;
		border: none;
		padding: 0;
		height: 20px;
		width: 20px;
		display: flex;
		align-items: center;
		justify-content: center;
	}

	.password-toggle input {
		padding-right: 35px;
		height: auto;
	}

	#currentPassword,
	#newPassword,
	#confirmPassword {
		height: 40px;
		box-sizing: border-box;
	}

	/* Toast Notification Styles */
	.toast-container {
		position: fixed;
		top: 20px;
		right: 20px;
		z-index: 9999;
		display: flex;
		flex-direction: column;
		gap: 10px;
	}

	.toast {
		padding: 15px 20px;
		border-radius: 6px;
		color: white;
		font-weight: 500;
		display: flex;
		align-items: center;
		justify-content: space-between;
		box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
		transform: translateX(150%);
		transition: transform 0.3s ease-out;
		max-width: 350px;
		opacity: 0;
		transition: opacity 0.3s, transform 0.3s;
	}

	.toast.show {
		transform: translateX(0);
		opacity: 1;
	}

	.toast-success {
		background-color: var(--success-color);
	}

	.toast-error {
		background-color: var(--error-color);
	}

	.toast-warning {
		background-color: var(--warning-color);
	}

	.toast-close {
		background: none;
		border: none;
		color: white;
		font-size: 18px;
		margin-left: 15px;
		cursor: pointer;
	}

	.toast-icon {
		margin-right: 10px;
		font-size: 20px;
	}

	/* Mobile specific modal styles */
	@media (max-width: 768px) {
		.photo-modal-content {
			position: fixed;
			bottom: 0;
			left: 0;
			right: 0;
			width: 100%;
			border-radius: 12px 12px 0 0;
			transform: translateY(100%);
			transition: transform 0.3s ease-out;
			padding: 20px;
		}

		.photo-modal-content.show {
			transform: translateY(0);
		}

		.photo-options {
			flex-direction: column;
			width: 100%;
			gap: 10px;
		}

		.photo-option-btn {
			width: 100%;
			padding: 12px;
			font-size: 16px;
		}

		.modal-content-form {
			width: 90%;
			padding: 20px;
			max-height: 80vh;
		}

		.modal-title {
			font-size: 20px;
		}

		.mobile-profile-header {
			flex-direction: column;
			text-align: center;
			padding: 30px 20px;
		}

		.mobile-profile-photo {
			width: 130px;
			height: 130px;
		}

		.dashboard {
			display: none;
		}

		.mobile-container {
			display: block;
			min-height: 100vh;
			background-color: var(--light);
		}

		body {
			display: block;
			background: var(--light);
		}

		/* Mobile Quick Actions Grid */
		.mobile-quick-actions {
			display: grid;
			grid-template-columns: repeat(auto-fill, minmax(100px, 1fr));
			gap: 15px;
		}

		.mobile-quick-actions .action-button {
			margin-bottom: 0;
			padding: 15px 10px;
		}

		/* Mobile header with home button and theme toggle */
		.mobile-header-actions {
			display: flex;
			justify-content: space-between;
			align-items: center;
			padding: 10px 15px;
			background-color: var(--card-bg);
			border-bottom: 1px solid var(--medium);
		}
	}

	@media (max-width: 576px) {
		.mobile-profile-header {
			flex-direction: column;
			text-align: center;
			padding: 30px 20px;
		}

		.mobile-profile-photo {
			width: 120px;
			height: 120px;
		}

		.info-grid {
			grid-template-columns: 1fr;
		}

		/* Single column for very small screens */
		.mobile-quick-actions {
			grid-template-columns: repeat(auto-fill, minmax(100px, 1fr));
			gap: 15px;
		}
	}
	</style>
</head>

<body>
	<!-- Desktop View -->
	<div class="dashboard">
		<div class="sidebar">
			<div class="profile">
			  <div class="bubble-container">
				<div class="bubble" style="--x: 200px; --y: -150px;"></div>
				<div class="bubble" style="--x: -150px; --y: -200px;"></div>
				<div class="bubble" style="--x: 100px; --y: 250px;"></div>
				<div class="bubble" style="--x: -120px; --y: 100px;"></div>
				<div class="bubble" style="--x: 180px; --y: -100px;"></div>
				<div class="bubble" style="--x: 140px; --y: 180px;"></div>
				<div class="bubble" style="--x: -200px; --y: 80px;"></div>
				<div class="bubble" style="--x: 90px; --y: -160px;"></div>
				<div class="bubble" style="--x: -160px; --y: 140px;"></div>
				<div class="bubble" style="--x: 200px; --y: -120px;"></div>
			</div>
				<img src="<?= gravatar_url($current_user['email'], 150); ?>"alt="Profile Photo" class="mobile-profile-photo" id="mobileProfilePhoto">
				<h2 class="profile-name"><?php echo  $current_user['name']; ?></h2>
				<div class="profile-rank"><?php echo  $current_user['rank']; ?></div>
				<div class="profile-service">BD/<?php echo  $_SESSION['user']; ?></div>
			</div>
			<a class="nav-item nav-home" onclick="window.location.href='index.php'">
				<i class="fas fa-home"></i>
				<span>Home</span>
			</a>
			<div class="sidebarscroll">  <div class="quick-actions-section">
					<div class="nav-item quick-actions-title collapsed" id="quickActionsToggle">
						<i class="fas fa-bolt"></i>
						<span>Quick Actions</span>
						<i class="fas fa-chevron-down drpicon"></i>
					</div>
					<div class="quick-actions-content" id="quickActionsContent">
						<div class="nav-menu">
							<button class="nav-item" onclick="window.location.href='index.php'">
								<i class="fa fa-shopping-bag"></i>
								<span>Supply</span>
							</button>
							<button class="nav-item" onclick="window.location.href='ex/base.php'">
								<i class="fas fa-phone-square"></i>
								<span>Exchange Tele</span>
							</button>
							<a href="notification.php" class="nav-item">
								<div class="notification-container">
									<i class="fas fa-bell"></i>
									<span class="notification-badge">3</span>
								</div>
								<span>Notifications</span>
							</a>
							<a href="ex/bus.php" class="nav-item">
								<i class="fa fa-bus"></i>
								<span>Bus Schedule</span>
							</a>
							<a href="#" class="nav-item">
								<i class="fas fa-cog"></i>
								<span>Settings</span>
							</a>
						</div>
					</div>
				</div>  <div class="nav-menu">
					<a href="index.php" class="nav-item ">
						<i class="fas fa-shopping-bag"></i>
						<span>Supply</span>
					</a>
					<a href="" class="nav-item active" id="desktopProfileTab">
						<i class="fas fa-address-book"></i>
						<span>About</span>
					</a>
					<a href="ex/base.php" class="nav-item ">
						<i class="fas fa-phone-square"></i> Base Exchange </a>
					<a href="ex/tk.php" class="nav-item ">
				<i class="fa fa-bank"></i>
				<span> Money tracker
						</span>
					</a>
  <a href="logout.php" class="nav-item logout-button">
						<i class="fas fa-sign-out-alt"></i>
						<span>Logout</span>
					</a>
				</div>
			</div>
		</div>
		<!-- In the main content section of desktop view -->
		<div class="main-content">
			<div class="header">
				<h1 class="page-title">Personnel Profile</h1>
				<div class="header-actions">
					<div>
						<input type="checkbox" class="checkbox" id="darkModeToggle">
						<label for="darkModeToggle" class="checkbox-label">
							<i class="fas fa-moon"></i>
							<i class="fas fa-sun"></i>
							<span class="ball"></span>
						</label>
					</div>
				</div>
			</div>
			<!-- Default About content -->
			<div id="defaultAboutContent" style="display: none;"> <div class="card">
            <h3 class="card-title"><i class="fas fa-info-circle"></i> Personal Information</h3>
            <div class="info-grid">
                <div class="info-item">
                    <div class="info-label">Service No</div>
                    <div class="info-value">:<?php echo $_SESSION['user']; ?></div>
                </div>
                <div class="info-item">
                    <div class="info-label">Rank</div>
                    <div class="info-value">LAC</div>
                </div><div class="info-item">
                <div class="info-label">Email</div>
   
            </div></div></div> </div>
			<!-- Dynamic content container -->
			<div id="dynamicContent" style="display: block;"> <div class='card'><i class="fas fa-info-circle"></i> Personal Information</h3>
			  <div class="info-grid">
            <div class="info-item">
                    <div class="info-label">Service No</div>
                    <div class="info-value"><?php echo $_SESSION['user']; ?></div>
                </div>
       <div class="info-item">
         <div class="info-label">Rank</div>
          <div class="info-value"><?php echo  $current_user['rank']; ?> <?php echo  $current_user['name']; ?>
          </p></div>
       </div><div class="info-item">
         <div class="info-label">Email</div>
                <div class="info-value"> <?php echo $current_user['email']; ?></p></div>
            </div><div class="info-item">
                <div class="info-label">Phone</div>
                <div class="info-value"> <?php echo $current_user['phone']; ?></p></div>
            </div>              
                </div>
			  </div> </div>
		</div>
	</div>
	<!-- Mobile View -->
	<div class="mobile-container">
		<!-- Profile Header -->
		<div class="mobile-profile-header">
			<div class="bg-deco blob1"></div>
			<div class="bg-deco blob2"></div>
			<div class="bg-deco blob3"></div>
			<div class="bg-deco bg-stripe"></div>
			<div class="bubble-container">
				<div class="bubble" style="--x: 200px; --y: -150px;"></div>
				<div class="bubble" style="--x: -150px; --y: -200px;"></div>
				<div class="bubble" style="--x: 100px; --y: 250px;"></div>
				<div class="bubble" style="--x: -120px; --y: 100px;"></div>
				<div class="bubble" style="--x: 180px; --y: -100px;"></div>
				<div class="bubble" style="--x: 140px; --y: 180px;"></div>
				<div class="bubble" style="--x: -200px; --y: 80px;"></div>
				<div class="bubble" style="--x: 90px; --y: -160px;"></div>
				<div class="bubble" style="--x: -160px; --y: 140px;"></div>
				<div class="bubble" style="--x: 200px; --y: -120px;"></div>
			</div>
				<img src="<?= gravatar_url($current_user['email'], 150); ?>"alt="Profile Photo" class="mobile-profile-photo" id="mobileProfilePhoto">
			<div class="mobile-profile-info">
				<h1 class="mobile-profile-name"><?php echo  $current_user['name']; ?></h1>
				<p class="mobile-profile-rank"><?php echo  $current_user['rank']; ?></p>
			</div>
		</div>
		<!-- Mobile Header with Home and Theme Toggle -->
		<div class="mobile-header-actions">
			<button class="home-button" onclick="window.location.href='index.php'">
				<i class="fas fa-home"></i>
				<span>Home</span>
			</button>
			<div>
				<input type="checkbox" class="checkbox" id="mobileDarkModeToggle">
				<label for="mobileDarkModeToggle" class="checkbox-label">
					<i class="fas fa-moon"></i>
					<i class="fas fa-sun"></i>
					<span class="ball"></span>
				</label>
			</div>
		</div>
		<!-- Mobile Tabs Navigation -->
		<div class="mobile-tabs-container">
			<div class="scroll-indicator left">
				<i class="fas fa-chevron-left"></i>
			</div>
			<div class="mobile-tabs-scroll" id="mobileTabsScroll">  <button class="mobile-tab-button " data-tab="quick-actions"> Quick Actions </button>  <a href="index.php" class="mobile-tab-button "><i class="fa fa-shopping-bag "></i> Supply </a>
				<a href="" class="mobile-tab-button active"><i class="fa fa-address-book"></i> About </a>
				<a href="ex/base.php" class="mobile-tab-button "><i class="fas fa-phone-square"></i> Base Exchange </a>
				<a href="ex/bus.php" class="mobile-tab-button "><i class="fa fa-bus"></i> Bus Schedule</a>
			<div class="scroll-indicator right">
				<i class="fas fa-chevron-right"></i>
			</div>
		</div>
		<!-- Content Area -->
		<div class="mobile-content">  <div id="quick-actions" class="mobile-tab-content ">
				<div class="card">
					<h3 class="card-title"><i class="fas fa-bolt"></i> Quick Actions</h3>
					<div class="mobile-quick-actions">
						<button class="action-button" onclick="window.location.href='index.php'">
							<i class="fa fa-shopping-bag"></i>
							<span>Supply</span>
						</button>
						<button class="action-button" onclick="window.location.href='ex/base.php'">
							<i class="fas fa-phone-square"></i>
							<span>Exchange Tele</span>
						</button>
						<button class="action-button" onclick="window.location.href='notification.php'">
							<div class="notification-container">
								<i class="fas fa-bell"></i>
								<span class="notification-badge" style="display: flex;">3</span>
							</div>
							<span>Notifications</span>
						</button>
						<button class="action-button" onclick="window.location.href='ex/tk.php'">
							<i class="fa fa-bank"></i>
							<span>Money Tracker</span>
						</button>
						<button class="action-button">
							<i class="fas fa-cog"></i>
							<span>Settings</span>
						</button>
						<button class="action-button logout-button" onclick="window.location.href='logout.php'">
							<i class="fas fa-sign-out-alt"></i>
							<span>Logout</span>
						</button>
					</div>
				</div>
			</div>  <div id="about" class="mobile-tab-content "> <div class="card">
            </div></div> </div>  <div id="documents" class="mobile-tab-content active"> <div class='card'><h3 class="card-title"><i class="fas fa-info-circle"></i> Personal Information</h3>
            <div class="info-grid">
                <div class="info-item">
                    <div class="info-label">Service No</div>
                    <div class="info-value"><?php echo $_SESSION['user']; ?></div>
                </div>
                <div class="info-item">
                    <div class="info-label">Rank</div>
                    <div class="info-value"><?php echo  $current_user['rank']; ?></p></div>
                </div><div class="info-item">
                <div class="info-label">Email</div>
                <div class="info-value"> <?php echo $current_user['email']; ?></p></div>
            </div><div class="info-item">
                <div class="info-label">Phone</div>
                <div class="info-value"> <?php echo $current_user['phone']; ?></p></div>
            </div></div> </div>  </div>
	</div>
	<!-- Profile Photo Modal -->
	<div id="profilePhotoModal" class="profile-photo-modal">
		<div class="photo-modal-overlay"></div>
		<div class="photo-modal-content">
			<div class="photo-options">
				<button id="viewPhotoBtn" class="photo-option-btn">View</button>  <button id="updatePhotoBtn" class="photo-option-btn">Update</button>  </div>
		</div>
	</div>
	<!-- Fullscreen Photo View -->
	<div id="photoFullscreen" class="photo-fullscreen" style="display: none;">
		<span class="close-fullscreen">&times;</span>
		<img id="fullscreenPhoto" src="" alt="Fullscreen Profile Photo">
	</div>
	<!-- Edit Profile Modal -->
	<div id="editProfileModal" class="form-modal">
		<div class="form-modal-overlay"></div>
		<div class="modal-content-form">
			<div class="modal-header">
				<h3 class="modal-title">Edit Profile</h3>
				<button class="close-modal">&times;</button>
			</div>
			<form id="editProfileForm" method="POST">
				<input type="hidden" name="edit_profile" value="1">
				<div class="form-group">
					<label for="editName" class="form-label">Full Name</label>
					<input type="text" id="editName" name="name" class="form-control" value="Jahid Al Mahmud" required>
				</div>  <div class="form-group">
					<label for="editRank" class="form-label">Rank</label>
					<select id="editRank" name="rank" class="form-select">
						<option value="">Select Rank</option>
						<option value="AC-1" >AC-1</option>
						<option value="LAC" selected>LAC</option>
						<option value="CPL" >CPL</option>
						<option value="SGT" >SGT</option>
						<option value="WO" >WO</option>
						<option value="SWO" >SWO</option>
						<option value="MWO" >MWO</option>
					</select>
				</div>  <div class="form-group">
					<label for="editPhone" class="form-label">Phone Number</label>
					<input type="tel" id="editPhone" name="phone" class="form-control" value="01724073223">
				</div>
				<div class="form-group">
					<label for="editBlood" class="form-label">Blood Group</label>
					<select id="editBlood" name="blood" class="form-select">
						<option value="">Select Blood Group</option>
						<option value="A+" >A+</option>
						<option value="A-" selected>A-</option>
						<option value="B+" >B+</option>
						<option value="B-" >B-</option>
						<option value="AB+" >AB+</option>
						<option value="AB-" >AB-</option>
						<option value="O+" >O+</option>
						<option value="O-" >O-</option>
					</select>
				</div>
				<div class="form-group">
					<label for="editBase" class="form-label">Base</label>
					<select id="editBase" name="base" class="form-select">
						<option value="">Select Base</option>
						<option value="AHQ" >AHQ</option>
						<option value="BSR" selected>BSR</option>
						<option value="AKR" >AKR</option>
						<option value="MTR" >MTR</option>
						<option value="ZHR" >ZHR</option>
						<option value="SMD" >SMD</option>
						<option value="CXB" >CXB</option>
					</select>
				</div>
				<div class="form-group">
					<label for="editUnit" class="form-label">Unit</label>
					<input type="text" id="editUnit" name="unit" class="form-control" value="M/W">
				</div>
				<div class="form-group">
					<button type="submit" class="btn btn-primary btn-block">Save Changes</button>
				</div>
			</form>
		</div>
	</div>
	<!-- Change Password Modal -->
	<div id="changePasswordModal" class="form-modal">
		<div class="form-modal-overlay"></div>
		<div class="modal-content-form">
			<div class="modal-header">
				<h3 class="modal-title">Change Password</h3>
				<button class="close-modal">&times;</button>
			</div>
			<form id="changePasswordForm" method="POST">
				<input type="hidden" name="change_password" value="1">
				<div class="form-group">
					<label for="currentPassword" class="form-label">Current Password</label>
					<div class="password-toggle">
						<input type="password" id="currentPassword" name="current_password" class="form-control" required>
						<i class="fas fa-eye password-toggle-icon" id="toggleCurrentPassword"></i>
					</div>
				</div>
				<div class="form-group">
					<label for="newPassword" class="form-label">New Password</label>
					<div class="password-toggle">
						<input type="password" id="newPassword" name="new_password" class="form-control" required>
						<i class="fas fa-eye password-toggle-icon" id="toggleNewPassword"></i>
					</div>
					<small class="text-muted">Password must be at least 8 characters long</small>
				</div>
				<div class="form-group">
					<label for="confirmPassword" class="form-label">Confirm New Password</label>
					<div class="password-toggle">
						<input type="password" id="confirmPassword" name="confirm_password" class="form-control" required>
						<i class="fas fa-eye password-toggle-icon" id="toggleConfirmPassword"></i>
					</div>
				</div>
				<div class="form-group">
					<button type="submit" class="btn btn-primary btn-block">Change Password</button>
				</div>
			</form>
		</div>
	</div>
	<!-- Toast Notifications Container -->
	<div id="toastContainer" class="toast-container"></div>
	<script>
	document.addEventListener('DOMContentLoaded', function() {
		// Theme Toggle Functionality
		const darkModeToggle = document.getElementById('darkModeToggle');
		const mobileDarkModeToggle = document.getElementById('mobileDarkModeToggle');
		const body = document.body;
		// Initialize dark mode from localStorage
		if(localStorage.getItem('darkMode') === 'enabled') {
			body.classList.add('dark-mode');
			if(darkModeToggle) darkModeToggle.checked = true;
			if(mobileDarkModeToggle) mobileDarkModeToggle.checked = true;
		}

		function toggleDarkMode(enable) {
			if(enable) {
				body.classList.add('dark-mode');
				localStorage.setItem('darkMode', 'enabled');
			} else {
				body.classList.remove('dark-mode');
				localStorage.setItem('darkMode', 'disabled');
			}
		}
		// Desktop theme toggle
		if(darkModeToggle) {
			darkModeToggle.addEventListener('change', function() {
				toggleDarkMode(this.checked);
				if(mobileDarkModeToggle) mobileDarkModeToggle.checked = this.checked;
			});
		}
		// Mobile theme toggle
		if(mobileDarkModeToggle) {
			mobileDarkModeToggle.addEventListener('change', function() {
				toggleDarkMode(this.checked);
				if(darkModeToggle) darkModeToggle.checked = this.checked;
			});
		}
		// Quick Actions Collapse/Expand - Without localStorage
		const quickActionsToggle = document.getElementById('quickActionsToggle');
		const quickActionsContent = document.getElementById('quickActionsContent');
		if(quickActionsToggle && quickActionsContent) {
			// Optional: Start collapsed or expanded by default (here: expanded)
			quickActionsToggle.classList.add('collapsed');
			quickActionsContent.classList.remove('expanded');
			quickActionsToggle.addEventListener('click', function(e) {
				e.stopPropagation(); // Prevent event bubbling
				this.classList.toggle('collapsed');
				quickActionsContent.classList.toggle('expanded');
			});
		}
		// Mobile Tab Switching
		const mobileTabButtons = document.querySelectorAll('.mobile-tab-button');
		mobileTabButtons.forEach(button => {
			button.addEventListener('click', function(e) {
				const tab = this.getAttribute('data-tab') || this.getAttribute('href').split('?')[1].split('&')[0];
				if(tab === 'quick-actions' || tab === 'about') {
					e.preventDefault();
					switchMobileTab(tab);
				}
			});
		});

		function switchMobileTab(tab) {
			document.querySelectorAll('.mobile-tab-button').forEach(btn => {
				btn.classList.remove('active');
			});
			document.querySelectorAll('.mobile-tab-content').forEach(content => {
				content.classList.remove('active');
			});
			const activeButton = document.querySelector(`.mobile-tab-button[data-tab="${tab}"], 
                                                      .mobile-tab-button[href*="${tab}"]`);
			if(activeButton) {
				activeButton.classList.add('active');
			}
			const activeContent = document.getElementById(tab);
			if(activeContent) {
				activeContent.classList.add('active');
			}
		}
		// Safely get current tab with fallback
		const currentTab = 'documents';
		const isOwnProfile = true;
		// Only initialize if elements exist
		if(document.getElementById('defaultAboutContent') && document.getElementById('dynamicContent')) {
			if(isOwnProfile && currentTab === 'quick-actions') {
				document.getElementById('defaultAboutContent').style.display = 'block';
				document.getElementById('dynamicContent').style.display = 'none';
			}
		}
		// Desktop profile tab handling
		const desktopProfileTab = document.getElementById('desktopProfileTab');
		const defaultAboutContent = document.getElementById('defaultAboutContent');
		const dynamicContent = document.getElementById('dynamicContent');
		// if (desktopProfileTab) {
		//     desktopProfileTab.addEventListener('click', function(e) {
		//         e.preventDefault();
		//         // Remove active class from all tabs except quick actions
		//         document.querySelectorAll('.nav-item:not(.quick-actions-title)').forEach(item => {
		//             item.classList.remove('active');
		//         });
		//         // Add active class to profile tab
		//         this.classList.add('active');
		//         // Show about content and hide dynamic content
		//         if (defaultAboutContent) defaultAboutContent.style.display = 'block';
		//         if (dynamicContent) dynamicContent.style.display = 'none';
		//     });
		// }
		// // Handle other tab clicks (non-profile tabs)
		// document.querySelectorAll('.nav-item:not(#desktopProfileTab):not(.quick-actions-title)').forEach(item => {
		//     item.addEventListener('click', function(e) {
		//         // Remove active class from all tabs except quick actions
		//         document.querySelectorAll('.nav-item:not(.quick-actions-title)').forEach(item => {
		//             item.classList.remove('active');
		//         });
		//         // Add active class to clicked tab
		//         this.classList.add('active');
		//         // Hide about content and show dynamic content
		//         if (defaultAboutContent) defaultAboutContent.style.display = 'none';
		//         if (dynamicContent) dynamicContent.style.display = 'block';
		//     });
		// });
		// Only apply to main navigation tabs, not quick actions
		document.querySelectorAll('.nav-menu > .nav-item:not(#editProfileBtn):not(#changePasswordBtn)').forEach(item => {
			item.addEventListener('click', function(e) {
				if(this.href && this.href !== '#') {
					// This is a navigation link, let it handle the content change
					return;
				}
				e.preventDefault();
				document.querySelectorAll('.nav-menu > .nav-item').forEach(navItem => {
					navItem.classList.remove('active');
				});
				this.classList.add('active');
				if(this.id === 'desktopProfileTab') {
					if(defaultAboutContent) defaultAboutContent.style.display = 'block';
					if(dynamicContent) dynamicContent.style.display = 'none';
				} else {
					if(defaultAboutContent) defaultAboutContent.style.display = 'none';
					if(dynamicContent) dynamicContent.style.display = 'block';
				}
			});
		});
		// Function to load tab content
		function loadTabContent(tab) {
			const mainContent = document.querySelector('.main-content');
			if(mainContent) {
				fetch(`profile.php?${tab}`).then(response => response.text()).then(html => {
					const parser = new DOMParser();
					const doc = parser.parseFromString(html, 'text/html');
					const newContent = doc.querySelector('.main-content');
					if(newContent) {
						mainContent.innerHTML = newContent.innerHTML;
					}
				});
			}
		}
		// Mobile scroll indicators
		const mobileTabsScroll = document.getElementById('mobileTabsScroll');
		const mobileLeftIndicator = document.querySelector('.mobile-tabs-container .scroll-indicator.left');
		const mobileRightIndicator = document.querySelector('.mobile-tabs-container .scroll-indicator.right');

		function updateMobileScrollIndicators() {
			if(!mobileTabsScroll) return;
			const scrollLeft = mobileTabsScroll.scrollLeft;
			const scrollWidth = mobileTabsScroll.scrollWidth;
			const clientWidth = mobileTabsScroll.clientWidth;
			if(mobileLeftIndicator) {
				mobileLeftIndicator.style.opacity = scrollLeft > 0 ? '1' : '0';
			}
			if(mobileRightIndicator) {
				mobileRightIndicator.style.opacity = scrollLeft < (scrollWidth - clientWidth - 1) ? '1' : '0';
			}
		}
		if(mobileLeftIndicator) {
			mobileLeftIndicator.addEventListener('click', function(e) {
				e.stopPropagation();
				if(mobileTabsScroll) {
					mobileTabsScroll.scrollBy({
						left: -100,
						behavior: 'smooth'
					});
				}
			});
		}
		if(mobileRightIndicator) {
			mobileRightIndicator.addEventListener('click', function(e) {
				e.stopPropagation();
				if(mobileTabsScroll) {
					mobileTabsScroll.scrollBy({
						left: 100,
						behavior: 'smooth'
					});
				}
			});
		}
		if(mobileTabsScroll) {
			updateMobileScrollIndicators();
			mobileTabsScroll.addEventListener('scroll', updateMobileScrollIndicators);
		}
		// Profile Photo Modal Functionality
		const profilePhoto = document.getElementById('profilePhoto');
		const mobileProfilePhoto = document.getElementById('mobileProfilePhoto');
		const profilePhotoModal = document.getElementById('profilePhotoModal');
		const photoModalOverlay = document.querySelector('.photo-modal-overlay');
		const photoModalContent = document.querySelector('.photo-modal-content');
		const viewPhotoBtn = document.getElementById('viewPhotoBtn');
		const updatePhotoBtn = document.getElementById('updatePhotoBtn');
		const photoFullscreen = document.getElementById('photoFullscreen');
		const fullscreenPhoto = document.getElementById('fullscreenPhoto');
		const closeFullscreen = document.querySelector('.close-fullscreen');
		let currentPhotoSrc = '';

		function positionPhotoModal() {
			if(window.innerWidth > 768) {
				const photoRect = profilePhoto.getBoundingClientRect();
				photoModalContent.style.top = `${photoRect.bottom + 10}px`;
				photoModalContent.style.left = `${photoRect.left + (photoRect.width - photoModalContent.offsetWidth) / 2}px`;
			}
		}

		function showFullscreenPhoto(src) {
			fullscreenPhoto.src = src;
			photoFullscreen.style.display = 'flex';
			document.body.style.overflow = 'hidden';
		}

		function closeFullscreenPhoto() {
			photoFullscreen.style.display = 'none';
			document.body.style.overflow = 'auto';
		}

		function closePhotoModal() {
			if(window.innerWidth <= 768) {
				photoModalContent.classList.remove('show');
				setTimeout(() => {
					profilePhotoModal.style.display = 'none';
				}, 300);
			} else {
				profilePhotoModal.style.display = 'none';
			}
			document.body.style.overflow = 'auto';
		}
		if(profilePhoto) {
			profilePhoto.addEventListener('click', function(e) {
				e.stopPropagation();
				currentPhotoSrc = this.src;
				positionPhotoModal();
				profilePhotoModal.style.display = 'block';
				if(window.innerWidth <= 768) {
					photoModalContent.classList.add('show');
				}
			});
		}
		if(mobileProfilePhoto) {
			mobileProfilePhoto.addEventListener('click', function(e) {
				e.stopPropagation();
				currentPhotoSrc = this.src;
				positionPhotoModal();
				profilePhotoModal.style.display = 'block';
				if(window.innerWidth <= 768) {
					photoModalContent.classList.add('show');
				}
			});
		}
		if(viewPhotoBtn) {
			viewPhotoBtn.addEventListener('click', function() {
				closePhotoModal();
				showFullscreenPhoto(currentPhotoSrc);
			});
		}
		if(updatePhotoBtn) {
			updatePhotoBtn.addEventListener('click', function() {
				window.location.href = 'photo.php';
			});
		}
		if(closeFullscreen) {
			closeFullscreen.addEventListener('click', closeFullscreenPhoto);
		}
		if(photoFullscreen) {
			photoFullscreen.addEventListener('click', function(e) {
				if(e.target === this) {
					closeFullscreenPhoto();
				}
			});
		}
		if(photoModalOverlay) {
			photoModalOverlay.addEventListener('click', closePhotoModal);
		}
		if(photoModalContent) {
			photoModalContent.addEventListener('click', function(e) {
				e.stopPropagation();
			});
		}
		window.addEventListener('resize', function() {
			if(profilePhotoModal.style.display === 'block') {
				positionPhotoModal();
			}
		});
		// Form Modals Management
		const editProfileModal = document.getElementById('editProfileModal');
		const changePasswordModal = document.getElementById('changePasswordModal');
		const editProfileBtn = document.getElementById('editProfileBtn');
		const mobileEditProfileBtn = document.getElementById('mobileEditProfileBtn');
		const changePasswordBtn = document.getElementById('changePasswordBtn');
		const mobileChangePasswordBtn = document.getElementById('mobileChangePasswordBtn');
		const closeModalButtons = document.querySelectorAll('.close-modal');
		const formModalOverlays = document.querySelectorAll('.form-modal-overlay');

		function showModal(modal) {
			modal.style.display = 'flex';
			document.body.style.overflow = 'hidden';
		}

		function hideModal(modal) {
			modal.style.display = 'none';
			document.body.style.overflow = 'auto';
		}
		if(editProfileBtn) {
			editProfileBtn.addEventListener('click', function(e) {
				e.preventDefault();
				e.stopPropagation(); // Prevent event from bubbling up
				showModal(editProfileModal);
			});
		}
		if(mobileEditProfileBtn) {
			mobileEditProfileBtn.addEventListener('click', function() {
				showModal(editProfileModal);
			});
		}
		if(changePasswordBtn) {
			changePasswordBtn.addEventListener('click', function(e) {
				e.preventDefault();
				e.stopPropagation(); // Prevent event from bubbling up
				showModal(changePasswordModal);
			});
		}
		if(mobileChangePasswordBtn) {
			mobileChangePasswordBtn.addEventListener('click', function() {
				showModal(changePasswordModal);
			});
		}
		closeModalButtons.forEach(button => {
			button.addEventListener('click', function() {
				const modal = this.closest('.form-modal');
				hideModal(modal);
			});
		});
		formModalOverlays.forEach(overlay => {
			overlay.addEventListener('click', function() {
				const modal = this.closest('.form-modal');
				hideModal(modal);
			});
		});
		// Password toggle functionality
		const toggleCurrentPassword = document.getElementById('toggleCurrentPassword');
		const toggleNewPassword = document.getElementById('toggleNewPassword');
		const toggleConfirmPassword = document.getElementById('toggleConfirmPassword');
		if(toggleCurrentPassword) {
			toggleCurrentPassword.addEventListener('click', function() {
				const passwordInput = document.getElementById('currentPassword');
				togglePasswordVisibility(passwordInput, this);
			});
		}
		if(toggleNewPassword) {
			toggleNewPassword.addEventListener('click', function() {
				const passwordInput = document.getElementById('newPassword');
				togglePasswordVisibility(passwordInput, this);
			});
		}
		if(toggleConfirmPassword) {
			toggleConfirmPassword.addEventListener('click', function() {
				const passwordInput = document.getElementById('confirmPassword');
				togglePasswordVisibility(passwordInput, this);
			});
		}

		function togglePasswordVisibility(input, icon) {
			if(input.type === 'password') {
				input.type = 'text';
				icon.classList.remove('fa-eye');
				icon.classList.add('fa-eye-slash');
			} else {
				input.type = 'password';
				icon.classList.remove('fa-eye-slash');
				icon.classList.add('fa-eye');
			}
		}
		//showToast function
		function showToast(message, type = 'success', duration = 5000) {
			const toastContainer = document.getElementById('toastContainer');
			const toast = document.createElement('div');
			toast.className = `toast toast-${type}`;
			const icon = type === 'success' ? 'fa-check-circle' : type === 'error' ? 'fa-exclamation-circle' : 'fa-info-circle';
			toast.innerHTML = `
                <div style="display: flex; align-items: center;">
                    <i class="fas ${icon} toast-icon"></i>
                    <span>${message}</span>
                </div>
                <button class="toast-close">&times;</button>
            `;
			toastContainer.appendChild(toast);
			void toast.offsetWidth;
			toast.classList.add('show');
			const closeBtn = toast.querySelector('.toast-close');
			closeBtn.addEventListener('click', () => {
				toast.remove();
			});
			if(duration > 0) {
				setTimeout(() => {
					toast.remove();
				}, duration);
			}
			return toast;
		}
		// Edit Profile Form Submission - AJAX
		const editProfileForm = document.getElementById('editProfileForm');
		if(editProfileForm) {
			editProfileForm.addEventListener('submit', function(e) {
				e.preventDefault();
				const formData = new FormData(this);
				const submitButton = this.querySelector('button[type="submit"]');
				const originalButtonText = submitButton.innerHTML;
				submitButton.disabled = true;
				submitButton.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processing...';
				fetch(window.location.href, {
					method: 'POST',
					body: formData,
					headers: {
						'Accept': 'application/json'
					}
				}).then(response => response.json()).then(data => {
					if(data.success) {
						showToast(data.message, 'success');
						if(data.data.name) {
							document.querySelectorAll('.profile-name').forEach(el => el.textContent = data.data.name);
							document.querySelectorAll('.mobile-profile-name').forEach(el => el.textContent = data.data.name);
							document.getElementById('editName').value = data.data.name;
						}
						if(data.data.phone) {
							document.querySelectorAll('.info-value:nth-child(5)').forEach(el => el.textContent = data.data.phone);
							document.getElementById('editPhone').value = data.data.phone;
						}
						if(data.data.blood) {
							document.querySelectorAll('.info-value:nth-child(8)').forEach(el => el.textContent = data.data.blood);
							document.getElementById('editBlood').value = data.data.blood;
						}
						if(data.data.base) {
							document.querySelectorAll('.info-value:nth-child(6)').forEach(el => el.textContent = data.data.base);
							document.getElementById('editBase').value = data.data.base;
						}
						if(data.data.unit) {
							document.querySelectorAll('.info-value:nth-child(7)').forEach(el => el.textContent = data.data.unit);
							document.getElementById('editUnit').value = data.data.unit;
						}
						if(data.data.rank) {
							document.querySelectorAll('.profile-rank').forEach(el => el.textContent = data.data.rank);
							document.querySelectorAll('.mobile-profile-rank').forEach(el => el.textContent = data.data.rank);
							if(document.getElementById('editRank')) {
								document.getElementById('editRank').value = data.data.rank;
							}
						}
						setTimeout(() => {
							hideModal(editProfileModal);
						}, 1000);
					} else {
						showToast(data.message, 'error');
					}
				}).catch(error => {
					console.error('Error:', error);
					showToast('Error updating profile', 'error');
				}).finally(() => {
					submitButton.disabled = false;
					submitButton.innerHTML = originalButtonText;
				});
			});
		}
		// Change Password Form Submission - AJAX
		const changePasswordForm = document.getElementById('changePasswordForm');
		if(changePasswordForm) {
			changePasswordForm.addEventListener('submit', function(e) {
				e.preventDefault();
				const formData = new FormData(this);
				const submitButton = this.querySelector('button[type="submit"]');
				const originalButtonText = submitButton.innerHTML;
				submitButton.disabled = true;
				submitButton.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processing...';
				fetch(window.location.href, {
					method: 'POST',
					body: formData,
					headers: {
						'Accept': 'application/json'
					}
				}).then(response => response.json()).then(data => {
					if(data.success) {
						showToast(data.message, 'success');
						this.reset();
						setTimeout(() => {
							hideModal(changePasswordModal);
						}, 1000);
					} else {
						showToast(data.message, 'error');
					}
				}).catch(error => {
					console.error('Error:', error);
					showToast('An error occurred while changing password', 'error');
				}).finally(() => {
					submitButton.disabled = false;
					submitButton.innerHTML = originalButtonText;
				});
			});
		}
	});
	</script>
</body>

</html>