/*
Navicat MySQL Data Transfer

Source Server         : localWin
Source Server Version : 50621
Source Host           : localhost:3306
Source Database       : nesoftwa_VGM

Target Server Type    : MYSQL
Target Server Version : 50621
File Encoding         : 65001

Date: 2016-05-12 11:54:55
*/

SET FOREIGN_KEY_CHECKS=0;

-- ----------------------------
-- Table structure for PERMISO
-- ----------------------------
DROP TABLE IF EXISTS `PERMISO`;
CREATE TABLE `PERMISO` (
  `id_permiso` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `permiso` varchar(50) DEFAULT '',
  PRIMARY KEY (`id_permiso`)
) ENGINE=MyISAM AUTO_INCREMENT=3 DEFAULT CHARSET=latin1;

-- ----------------------------
-- Records of PERMISO
-- ----------------------------
INSERT INTO `PERMISO` VALUES ('1', 'Admin');
INSERT INTO `PERMISO` VALUES ('2', 'User');

-- ----------------------------
-- Table structure for REL_USR_PERM
-- ----------------------------
DROP TABLE IF EXISTS `REL_USR_PERM`;
CREATE TABLE `REL_USR_PERM` (
  `id_rel_usr_perm` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `id_usuario` int(11) DEFAULT '0',
  `id_permiso` int(11) DEFAULT '0',
  PRIMARY KEY (`id_rel_usr_perm`)
) ENGINE=MyISAM AUTO_INCREMENT=3 DEFAULT CHARSET=latin1;

-- ----------------------------
-- Records of REL_USR_PERM
-- ----------------------------
INSERT INTO `REL_USR_PERM` VALUES ('1', '1', '1');
INSERT INTO `REL_USR_PERM` VALUES ('2', '1', '2');

-- ----------------------------
-- Table structure for USUARIO
-- ----------------------------
DROP TABLE IF EXISTS `USUARIO`;
CREATE TABLE `USUARIO` (
  `id_user` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(50) DEFAULT '',
  `password` varchar(32) DEFAULT '',
  `company` varchar(100) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `email_cc` text,
  `sender_id` varchar(15) DEFAULT NULL,
  `nota` text,
  `pass_text` varchar(32) DEFAULT NULL,
  PRIMARY KEY (`id_user`)
) ENGINE=MyISAM AUTO_INCREMENT=19 DEFAULT CHARSET=latin1;

-- ----------------------------
-- Records of USUARIO
-- ----------------------------
INSERT INTO `USUARIO` VALUES ('1', 'Nestor Perez', '202cb962ac59075b964b07152d234b70', 'NESOFTWARE S.A.', 'nestor@nesoftware.net', null, 'NES123', 'Nota xxx', '123');
INSERT INTO `USUARIO` VALUES ('2', 'MARCELA REED', '', 'AKZO NOBEL CHEMICALS, S.A. DE C.V.', 'demo@demo.com', null, null, null, null);
INSERT INTO `USUARIO` VALUES ('3', 'ANEYDA LOPERENA GONZALEZ', '', 'ALCOBE CERAMICOS, S.A. DE C.V.', 'demo@demo.com', null, null, null, null);
INSERT INTO `USUARIO` VALUES ('4', 'FRANTOARI R.', '', 'ALIMENTARIA MEXICANA BEKAREM, S.A. DE C.V.', 'demo@demo.com', null, null, null, null);
INSERT INTO `USUARIO` VALUES ('5', 'LAURA VIDAL', '', 'ALPLA MEXICO, S.A. DE C.V.', 'demo@demo.com', null, null, null, null);
INSERT INTO `USUARIO` VALUES ('6', 'VERONICA COLIN', '', 'ALTOS HORNOS DE MEXICO, S.A. DE C.V.', 'demo@demo.com', null, null, null, null);
INSERT INTO `USUARIO` VALUES ('7', 'VIRGINIA QUINTANA MARZANO', '', 'ALUMAC, S.A. DE C.V.', 'demo@demo.com', null, null, null, null);
INSERT INTO `USUARIO` VALUES ('8', 'GUADALUPE TORRES', '', 'ALL FREIGHT SERVICES, S.C.', 'demo@demo.com', null, null, null, null);
INSERT INTO `USUARIO` VALUES ('9', 'LUISA GRIMALDO', '', 'AMCO INTERNACIONAL, S.A. DE C.V.', 'demo@demo.com', null, null, null, null);
INSERT INTO `USUARIO` VALUES ('10', 'ADRIANA CUEVAS', '', 'AMERICAN CAR EQUIPMENT, S.A. DE C.V.', 'demo@demo.com', null, null, null, null);
INSERT INTO `USUARIO` VALUES ('11', 'MARTHA MANRIQUE', '', 'AMR SHIPPING, LTD.', 'demo@demo.com', null, null, null, null);
INSERT INTO `USUARIO` VALUES ('12', 'GABRIELA GARCIA', '', 'ADVANCED MARKETING, S. DE R.L. DE C.V.', 'demo@demo.com', null, null, null, null);
INSERT INTO `USUARIO` VALUES ('13', 'MIGUEL A. HERRERA', '', 'AREA SEGURA, S.A. DE C.V.', 'demo@demo.com', null, null, null, null);
INSERT INTO `USUARIO` VALUES ('14', 'ALEJANDRO VALENTINO', '', 'ARTES GRAFICAS UNIDAS, S.A. DE C.V.', 'demo@demo.com', null, null, null, null);
INSERT INTO `USUARIO` VALUES ('16', 'JOSÉ LUIS GALAVIZ', '', 'ASIMEX CARGO, S.A. DE C.V.', 'demo@demo.com', null, null, null, null);
INSERT INTO `USUARIO` VALUES ('17', 'PATRICIA OROPEZA', '', 'ATL PRIDA LOGISTIC, S.A. DE C.V.', 'demo@demo.com', null, null, null, null);
INSERT INTO `USUARIO` VALUES ('18', 'MILDRETT MORENO', '', 'BADER DE MEXICO, S. EN C. POR A. DE C.V.', 'demo@demo.com', null, null, null, null);

-- ----------------------------
-- Table structure for VESSEL
-- ----------------------------
DROP TABLE IF EXISTS `VESSEL`;
CREATE TABLE `VESSEL` (
  `id_vessel` int(11) NOT NULL AUTO_INCREMENT,
  `vessel` varchar(120) CHARACTER SET utf8 COLLATE utf8_spanish_ci DEFAULT NULL,
  `voyage` varchar(10) CHARACTER SET utf8 COLLATE utf8_spanish_ci DEFAULT NULL,
  `pol` varchar(5) CHARACTER SET utf8 COLLATE utf8_spanish_ci DEFAULT NULL,
  `pod` varchar(5) CHARACTER SET utf8 COLLATE utf8_spanish_ci DEFAULT NULL,
  `eta` date DEFAULT NULL,
  `etd` date DEFAULT NULL,
  `id_usr` mediumint(5) DEFAULT NULL,
  `f_captura` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_vessel`)
) ENGINE=MyISAM AUTO_INCREMENT=9 DEFAULT CHARSET=latin1;

-- ----------------------------
-- Records of VESSEL
-- ----------------------------
INSERT INTO `VESSEL` VALUES ('1', 'BARCO1', '0011', 'MXVER', 'JPYOK', '2016-05-11', '2016-05-12', '1', '2016-05-10 09:07:41');
INSERT INTO `VESSEL` VALUES ('2', 'BARCO2', 'L342', 'MXALT', 'CNSHA', '2016-03-01', '2016-03-02', '1', '2016-05-10 08:57:43');
INSERT INTO `VESSEL` VALUES ('3', 'BARCO3', 'V111', 'CNHWA', 'MXZLO', '2016-03-05', '2016-03-06', '1', '2016-05-10 08:21:25');
INSERT INTO `VESSEL` VALUES ('4', 'BARCO4', 'V222', 'CLARI', 'MXVER', '2016-04-01', '2016-04-02', '1', '2016-05-10 08:22:56');
INSERT INTO `VESSEL` VALUES ('6', 'BARCO Z', 'Z0001', 'XMVER', 'CCDEA', '2016-01-01', '2016-01-02', null, null);
INSERT INTO `VESSEL` VALUES ('7', 'BARCO5 INES', '111', null, null, null, null, null, '2016-05-12 11:38:18');
INSERT INTO `VESSEL` VALUES ('8', 'BARCO5 INGRIT', '222', null, null, null, null, null, '2016-05-12 11:38:24');
