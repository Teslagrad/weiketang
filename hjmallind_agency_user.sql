/*
Navicat MySQL Data Transfer

Source Server         : 120.25.250.197kafan
Source Server Version : 50729
Source Host           : 120.25.250.197:3306
Source Database       : kafang

Target Server Type    : MYSQL
Target Server Version : 50729
File Encoding         : 65001

Date: 2020-04-30 10:24:53
*/

SET FOREIGN_KEY_CHECKS=0;

-- ----------------------------
-- Table structure for hjmallind_agency_user
-- ----------------------------
DROP TABLE IF EXISTS `hjmallind_agency_user`;
CREATE TABLE `hjmallind_agency_user` (
  `userId` int(11) NOT NULL AUTO_INCREMENT COMMENT '用户id',
  `roleId` varchar(20) NOT NULL COMMENT '角色id（1-代理商；2-门店；3-员工）',
  `username` varchar(50) NOT NULL COMMENT '账号（手机号）',
  `name` varchar(20) NOT NULL COMMENT '姓名',
  `salt` varchar(100) NOT NULL COMMENT '盐',
  `password` varchar(100) NOT NULL COMMENT '密码',
  `openId` varchar(100) DEFAULT NULL COMMENT '用户openid',
  `parentId` varchar(20) DEFAULT NULL COMMENT '分销上级用户id',
  `totalPrice` decimal(10,2) DEFAULT '0.00' COMMENT '总收益',
  `storeAddress` varchar(200) DEFAULT NULL COMMENT '门店地址',
  `storeName` varchar(100) DEFAULT NULL COMMENT '门店名',
  `area` varchar(50) DEFAULT NULL COMMENT '代理商所管辖区域',
  `citys` varchar(255) DEFAULT NULL COMMENT '代理商区域下的城市，多个用逗号分割',
  `wechatName` varchar(20) DEFAULT NULL COMMENT '微信昵称',
  `photo` varchar(200) DEFAULT NULL COMMENT '微信头像',
  `loginIp` varchar(20) DEFAULT NULL COMMENT '最后登录IP',
  `loginTime` datetime DEFAULT NULL COMMENT '最后登录时间',
  `loginQty` int(11) DEFAULT '0' COMMENT '登录次数',
  `isDelete` tinyint(4) DEFAULT '0' COMMENT '是否删除(0--否,1--是)',
  `createTime` datetime DEFAULT NULL COMMENT '创建时间',
  `updateTime` datetime DEFAULT NULL COMMENT '最后修改时间',
  `accessToken` varchar(200) DEFAULT NULL COMMENT '访问token',
  `refreshToken` varchar(200) DEFAULT NULL COMMENT '刷新token',
  `expireTime` datetime DEFAULT NULL COMMENT '过期时间',
  PRIMARY KEY (`userId`) USING BTREE,
  UNIQUE KEY `idxUN` (`isDelete`,`username`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=51 DEFAULT CHARSET=utf8 COMMENT='用户信息表';
