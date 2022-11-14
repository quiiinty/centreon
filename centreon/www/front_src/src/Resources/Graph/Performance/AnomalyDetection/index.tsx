import { Dispatch, ReactNode, SetStateAction, useState } from 'react';

import { useAtom } from 'jotai';
import { useUpdateAtom } from 'jotai/utils';
import { useTranslation } from 'react-i18next';

import { Button, Dialog, Paper } from '@mui/material';
import makeStyles from '@mui/styles/makeStyles';

import {
  labelClose,
  labelEditAnomalyDetectionConfirmation,
  labelMenageEnvelope,
} from '../../../translatedLabels';
import TimePeriodButtonGroup from '../TimePeriods';

import {
  countedRedCirclesAtom,
  showModalAnomalyDetectionAtom,
} from './anomalyDetectionAtom';
import AnomalyDetectionModalConfirmation from './editDataDialog/AnomalyDetectionModalConfirmation';
import { CustomFactorsData } from './models';
import AnomalyDetectionExclusionPeriod from './exclusionPeriod/index';

const useStyles = makeStyles((theme) => ({
  close: {
    display: 'flex',
    justifyContent: 'flex-end',
  },
  container: {
    '& .MuiDialog-paper': {
      backgroundColor: theme.palette.background.default,
      maxWidth: '80%',
      padding: theme.spacing(2),
      width: '100%',
    },
  },
  editEnvelopeSize: {
    display: 'flex',
    justifyContent: 'space-between',
    width: '90%',
  },
  envelopeSize: {
    flex: 1,
    marginRight: theme.spacing(1),
  },
  exclusionPeriod: {
    flex: 2,
  },
  spacing: {
    paddingBottom: theme.spacing(1),
  },
}));

interface GraphProps {
  factorsData?: CustomFactorsData | null;
}

interface SliderProps {
  getFactors: (data: CustomFactorsData) => void;
  isEnvelopeResizingCanceled: boolean;
  isResizingEnvelope: boolean;
  openModalConfirmation: (value: boolean) => void;
  setIsResizingEnvelope: Dispatch<SetStateAction<boolean>>;
}

interface Props {
  renderGraph: (args: GraphProps) => ReactNode;
  renderSlider: (args: SliderProps) => ReactNode;
}

const EditAnomalyDetectionDataDialog = ({
  renderGraph,
  renderSlider,
}: Props): JSX.Element => {
  const classes = useStyles();
  const { t } = useTranslation();

  const [factorsData, setFactorsData] = useState<null | CustomFactorsData>(
    null,
  );
  const [isModalConfirmationOpened, setIsModalConfirmationOpened] =
    useState(false);

  const [isEnvelopeResizingCanceled, setIsEnvelopeResizingCanceled] =
    useState(false);

  const [isResizingEnvelope, setIsResizingEnvelope] = useState(false);

  const [showModalAnomalyDetection, setShowModalAnomalyDetection] = useAtom(
    showModalAnomalyDetectionAtom,
  );
  const setCountedRedCircles = useUpdateAtom(countedRedCirclesAtom);

  const handleClose = (): void => {
    setShowModalAnomalyDetection(false);
    setCountedRedCircles(null);
  };

  const getFactors = (data: CustomFactorsData): void => {
    setFactorsData(data);
  };

  const openModalConfirmation = (value: boolean): void => {
    setIsModalConfirmationOpened(value);
    setIsEnvelopeResizingCanceled(false);
  };
  const cancelResizeEnvelope = (value: boolean): void => {
    setIsEnvelopeResizingCanceled(value);
  };

  const resizeEnvelope = (value: boolean): void => {
    setIsResizingEnvelope(value);
    setIsModalConfirmationOpened(false);
  };

  return (
    <Dialog
      className={classes.container}
      data-testid="modalEditAnomalyDetection"
      open={showModalAnomalyDetection}
    >
      <div>
        <div className={classes.spacing}>
          <TimePeriodButtonGroup />
        </div>
        <div className={classes.spacing}>{renderGraph({ factorsData })}</div>
        <div className={classes.editEnvelopeSize}>
          <Paper className={classes.envelopeSize}>
            {renderSlider({
              getFactors,
              isEnvelopeResizingCanceled,
              isResizingEnvelope,
              openModalConfirmation,
              setIsResizingEnvelope,
            })}
          </Paper>
          <Paper className={classes.exclusionPeriod}>
            <EditAnomalyDetectionDataDialog.ExclusionPeriod />
          </Paper>
        </div>
        <EditAnomalyDetectionDataDialog.ModalConfirmation
          dataTestid="modalConfirmation"
          message={t(labelEditAnomalyDetectionConfirmation)}
          open={isModalConfirmationOpened}
          setOpen={setIsModalConfirmationOpened}
          title={t(labelMenageEnvelope)}
          onCancel={cancelResizeEnvelope}
          onConfirm={resizeEnvelope}
        />
        <div className={classes.close}>
          <Button data-testid="closeEditModal" onClick={handleClose}>
            {t(labelClose)}
          </Button>
        </div>
      </div>
    </Dialog>
  );
};

EditAnomalyDetectionDataDialog.ExclusionPeriod =
  AnomalyDetectionExclusionPeriod;
EditAnomalyDetectionDataDialog.ModalConfirmation =
  AnomalyDetectionModalConfirmation;

export default EditAnomalyDetectionDataDialog;
