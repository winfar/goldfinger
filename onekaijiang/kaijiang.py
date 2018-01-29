#coding=utf-8
import urllib2
import json
import time
import hashlib
from MySQL import *
from datetime import datetime

class KaiJiang(object):
    def __init__(self, dbconfig):
        self.dbconfig = dbconfig

    def GetSsc(self):
        url = 'http://chart.cp.360.cn/zst/qkj/?lotId=255401'
        content = urllib2.urlopen(url)
        if content.getcode()==200:
            sscJson=json.loads(content.read().decode())
            content.close()
            file_object = open('issue.txt','r')
            line = file_object.readline()
            file_object.close()
            if int(time.time())>int(sscJson['preEndTime']) and int(sscJson['0']['Issue'])>int(line):
                file_object = open('issue.txt','w')
                file_object.write(sscJson['0']['Issue'])
                file_object.close()
                return sscJson
            else:
                return self.GetSsc()
            file_object.close()
        else:
            return self.GetSsc()

    def TimeFactor(self):
        lists=[]
        if self.dbconfig['party']==1:
            resultssc=self.GetSsc()
            kaijiang_issue=resultssc['0']['Issue']
            kaijiang_ssc=int(resultssc['0']['WinNumber'])
        else:
            kaijiang_issue=160000000
            kaijiang_ssc=0
        print 'kaijiang_ssc: %s' % kaijiang_ssc
        print 'kaijiang_issue: %s' % kaijiang_issue
        db = MySQL(self.dbconfig)
        db.query('select kaijiang_count,kaijiang_ssc,number,id,kaijang_time,sid,no  from '+self.dbconfig['prefix']+'shop_period where state=1 and (kaijang_time-unix_timestamp(CURRENT_TIMESTAMP()))<60')
        result = db.fetchAllRows()
        print 'result fetched'
        if result:
            print 'enter if'
            for row in result:
                print 'enter for'
                kaijang_num = (row[0]+kaijiang_ssc)%row[2]+10000001
                print 'kaijang_num: %s' % kaijang_num
                db.query('select uid from '+self.dbconfig['prefix']+'shop_record where pid=%d and FIND_IN_SET("%d",num)' % (row[3],kaijang_num))
                uid = db.fetchOneRow()
                print 'uid fetched'
                if uid:
                    sqlupdate = 'update '+self.dbconfig['prefix']+'shop_period set kaijang_num=%s,uid=%d,state=2,kaijiang_ssc=%s,kaijiang_issue=%s where id=%d' % (kaijang_num,uid[0],kaijiang_ssc,kaijiang_issue,row[3])
                    db.update(sqlupdate)
                    debugstr = '%s \n pid | time: %s,%s \n kaijiang_issue: %s,kaijiang_ssc: %s' % (sqlupdate,row[3],datetime.now(),kaijiang_issue,kaijiang_ssc)
                    print debugstr
                    file_object = open('log.txt','a+')
                    file_object.write( debugstr )
                    file_object.close()
                    lists.append(row[3])
            print 'pid begin'
            for pid in lists:
                print 'enter for lists'            
                #url = 'http://passport.busonline.com/wapapi.php?s=Public/winningSendCode/id/%r' % pid
                url = 'http://test.passport.busonline.com/wapapi.php?s=/notification/winningSendCode/id/%r' % pid
                content = urllib2.urlopen(url)
                content.close()
        db.close()
