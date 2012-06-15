#! /usr/bin/python
import os
import sys
from pydas import core

# Load configuration file
def loadConfig(filename):
   try:
     configfile = open(filename, "r")
     ret = dict()
     for x in configfile:
       x = x.strip()
       if not x: continue
       cols = x.split()
       #print cols
       ret[cols[0]] = cols[1]
     return ret
   except Exception, e: raise



if __name__ == "__main__":
  print sys.argv
  (script_name, output_dir, output_file_path, output_folder_id, job, jobname, returncode) = sys.argv

  parts = output_file_path.split('/')
  outputFileDir = '/'.join(parts[:-1])
  outputFile = parts[-1]

  print "outputFile: ",outputFile
  print "outputFileDir: ",outputFileDir

  jobidNum = jobname[3:]
  cfgParams = loadConfig('config.cfg')

  postfilename = 'postscript'+jobidNum+'.log'
  log = open(os.path.join(output_dir, postfilename),'w')
  log.write('Condor Post Script log\n\nsys.argv:\n\n')
  log.write('\t'.join(sys.argv))

  log.write('\n\nConfig Params:\n\n')
  log.write('\n'.join(['\t'.join((k,v)) for (k,v) in cfgParams.iteritems()])) 

  interfaceMidas = core.Communicator (cfgParams['url'])
  token = interfaceMidas.login_with_api_key(cfgParams['email'], cfgParams['apikey'], application='Default')
  log.write("\n\nLogged into midas, got token: "+token+"\n\n")
  exeOutput = 'bmGrid.' + jobidNum + '.out.txt' 
  exeOutputPath = os.path.join(outputFileDir, exeOutput)
  

  # create the item
  item = interfaceMidas.create_item(token, outputFile, output_folder_id)
  itemId = item['item_id']
  log.write("\n\nCalled createItem, got itemId:"+str(itemId)+"\n\n")


  filePath = outputFileDir + '/' + outputFile
  uploadToken = interfaceMidas.generate_upload_token(token, itemId, outputFile)
  log.write("\n\nGot uploadToken:"+str(uploadToken)+" for filename "+outputFile+"\n\n")

  uploadResponse = interfaceMidas.perform_upload(uploadToken, outputFile, itemid=itemId, filepath=filePath)
  log.write("\n\nGot uploadResponse:"+str(uploadResponse)+" for filename "+outputFile+"\n\n")

